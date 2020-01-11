<?php

namespace App\Console\Commands;

use App\Models\Novel;
use Illuminate\Console\Command;
use QL\Ext\CurlMulti;
use QL\QueryList;

class update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清洗数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 清洗半年内未更新的数据
        Novel::where('serialize', 0)->chunk(2000, function ($novels) {

            // 引入多线程插件
            $ql = QueryList::getInstance();
            $ql->use(CurlMulti::class);
            //or Custom function name
            $ql->use(CurlMulti::class, 'curlMulti');

            try {
                $urls = [];
                foreach ($novels as $novel) {
                    $source_id = $novel->source_id;
                    $sub_dir = intval($source_id / 1000) + 1;
                    $urls[] = "http://infos.2otea.com/BookFiles/Html/{$sub_dir}/{$source_id}/info.html";
                }

                // 开始采集
                $ql->curlMulti($urls)
                    ->success(function (QueryList $ql, CurlMulti $curl, $r) {
                        echo "Current url:{$r['info']['url']} \r\n";
                        $json = $r['body'];
                        $ret_json = trim($json, "\xEF\xBB\xBF"); // 去掉BOM头信息
                        $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                        $response = json_decode($ret_json);
                        $response = $response->data;
                        $updated_at = strtotime($response->LastTime);
                        Novel::where('source_id', $response->Id)->update([
                            'img' => $response->Img,
                            'updated_at' => $updated_at
                        ]);
                        // 释放资源
                        $ql->destruct();
                    })
                    ->start([
                        'maxThread' => 10,
                        'cache' => [
                            'enable' => false,
                            'compress' => false,
                            'dir' => null,
                            'expire' => 86400,
                            'verifyPost' => false
                        ]
                    ]);
            } catch (\Exception $e) {
                // todo
            }
        });
    }
}
