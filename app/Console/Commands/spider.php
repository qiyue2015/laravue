<?php

namespace App\Console\Commands;

use App\Models\Novel;
use Illuminate\Console\Command;
use QL\Ext\CurlMulti;
use QL\QueryList;

class spider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:qu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // 引入多线程插件
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class);
        //or Custom function name
        $ql->use(CurlMulti::class, 'curlMulti');

        $x = 1;
        $j = 1;
        while ($x <= 51) {
            echo "\n\r------------------------ {$x} -----------------------\n\r";
            // 每次取 100
            $data = Novel::where('display', 1)->where('id', '>', $j)->orderBy('id', 'asc')->limit(1000)->get(['id', 'title', 'source_id']);
            if (!empty($data)) {
                $urls = [];
                foreach ($data as $row) {
                    if (empty($row['title'])) {
                        continue;
                    }
                    $source_id = intval($row['source_id']);
                    $sub_dir = intval($source_id / 1000) + 1;
                    // $urls[] = "http://iosapp.jiaston.com/book/{$row->id}/";
//                    $urls[] = "https://downbak.hzwip.com/BookFiles/Html/{$sub_dir}/{$source_id}/index.html";
                    $urls[] = "https://infos.2otea.com/BookFiles/Html/{$sub_dir}/{$source_id}/index.html";
                    // $j = $row->id;
                }
                if (count($urls)) {
                    $ql->curlMulti($urls)->success(function (QueryList $ql, CurlMulti $curl, $r) {

                        // echo "Current url:{$r['info']['url']} \r\n";

                        $html = $ql->getHtml();
                        $ret_json = trim($html, "\xEF\xBB\xBF"); // 去掉BOM头信息
                        $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                        $ret_arr = json_decode($ret_json, true);
                        if (!empty($ret_arr)) {

                            $novel_id = $ret_arr['data']['id'];
                            $novel_name = $ret_arr['data']['name'];

                            echo "{$novel_id} \t ";

                            // 章节例表
                            $list = $ret_arr['data']['list'];
                            if (!empty($list)) {

                                echo count($list) . "卷\t";
                                $xxxx = 0;
                                foreach ($list as $value) {
                                    $xxxx = $xxxx + count($value['list']);
                                }
                                echo $xxxx . "章\t";
                                echo PHP_EOL;

                                // 入库
                                $chapter = serialize(json_encode($list, JSON_UNESCAPED_UNICODE));
                                Novel::where('id', $novel_id)->update(['chapters' => $chapter, 'display' => 1]);
                            }
                        }
                        // 释放资源
                        $ql->destruct();
                    })->start([
                        'cache' => ['enable' => false, 'compress' => false, 'dir' => null, 'expire' => 86400, 'verifyPost' => false]
                    ]);
                }
            } else {
                exit('结束了');
            }
            $x++;
        }
    }
}
