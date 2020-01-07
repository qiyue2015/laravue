<?php

namespace App\Console\Commands;

use App\Models\Chapter;
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
    protected $signature = 'spider:qu';

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
        $last_id = 0;
        while ($x <= 1) {
            echo "\n\r------------------- Start {$x} > {$last_id} ------------------- \n\r";
            // 每次取 1000
            $data = Novel::where('display', 0)->where('id', '>', $last_id)->orderBy('id', 'asc')->limit(1000)->get(['id', 'title', 'source_id']);
            if (empty($data)) {
                exit('结束了');
            }
            // URL例表
            $urls = [];
            foreach ($data as $row) {
                $last_id = $row->id;
                if (empty($row['title'])) {
                    continue;
                }
                $source_id = intval($row['source_id']);
                $sub_dir = intval($source_id / 1000) + 1;
                // $urls[] = "http://iosapp.jiaston.com/book/{$row->id}/";
                // $urls[] = "https://downbak.hzwip.com/BookFiles/Html/{$sub_dir}/{$source_id}/index.html";
                $urls[] = "https://infos.2otea.com/BookFiles/Html/{$sub_dir}/{$source_id}/index.html";
            }

            // 开始采集
            $ql->curlMulti($urls)->success(function (QueryList $ql, CurlMulti $curl, $r) {

                //echo "Current url:{$r['info']['url']} \r\n";

                $html = $ql->getHtml();
                $ret_json = trim($html, "\xEF\xBB\xBF"); // 去掉BOM头信息
                $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                $ret_arr = json_decode($ret_json, true);

                if (!empty($ret_arr)) {

                    $novel_id = $ret_arr['data']['id'];
                    $novel_name = $ret_arr['data']['name'];

                    echo "{$novel_id}";

                    // 章节例表
                    $chapters_array = $ret_arr['data']['list'];
                    if (!empty($chapters_array)) {
                        foreach ($chapters_array as $list) {
                            $data = [
                                'novel_id' => $novel_id,
                                'volume_id' => 0,
                                'chapter_name' => '',
                                'chapter_type' => 0,
                                'chapter_order' => 0,
                                'source_chapter_id' => 0,
                                'display' => 1,
                                'has_content' => 0,
                                'created_at' => time(),
                                'updated_at' => time()
                            ];

                            // 分卷
                            $volume_name = trim($list['name']);
                            $volume_id = Chapter::where('novel_id', $novel_id)
                                ->where('chapter_type', 1)
                                ->where('chapter_name', $volume_name)
                                ->pluck('id')
                                ->first();
                            if (empty($volume_id)) {
                                $volume = $data;
                                $volume['chapter_type'] = 1;
                                $volume['chapter_name'] = $volume_name;
                                $volume['has_content'] = 0;
                                $volume = Chapter::create($volume);
                                $volume_id = $volume->id;
                            }

                            $add = [];
                            foreach ($list['list'] as $item) {
                                $data['volume_id'] = $volume_id;
                                $data['chapter_name'] = trim($item['name']);
                                $data['has_content'] = intval($item['hasContent']);
                                $add[] = $data;
                                echo '.';
                            }
                            Chapter::insert($add);
                        }
                        $count = Chapter::where(['novel_id' => $novel_id, 'chapter_order' => 0])->count();
                        Novel::where('id', $novel_id)->update(['chapter_count' => $count, 'display' => 1]);
                    }
                    echo PHP_EOL;
                }

                // 释放资源
                $ql->destruct();
            })->start([
                'cache' => [
                    'enable' => false,
                    'compress' => false,
                    'dir' => null,
                    'expire' => 86400,
                    'verifyPost' => false
                ]
            ]);

//            echo "\n\r------------------- End {$x} > {$last_id} ------------------- \n\r";
        }
    }
}
