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
     * 返回JSON内容
     * @param $url
     * @return mixed
     */
    public function getJson($url)
    {
        $json = QueryList::get($url)->getHtml();
        $ret_json = trim($json, "\xEF\xBB\xBF"); // 去掉BOM头信息
        $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
        return json_decode($ret_json);
    }

    public function handle_bbb()
    {
        $info_url = "http://downbak.hzwip.com/BookFiles/Html/%d/%d/info.html";
        $index_url = "http://infos.2otea.com/BookFiles/Html/%d/%d/index.html";
        $read_url = 'http://infos.2otea.com/BookFiles/Html/%d/%d/%d.html';

        // 引入多线程插件
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class);
        //or Custom function name
        $ql->use(CurlMulti::class, 'curlMulti');

        $i = 1;
        $last_id = 0;
        while ($i <= 2) {

            echo $i . ">>>\n\r";

            $source_id = $i;
            $sub_dir = intval($source_id / 1000) + 1;

            // 基本信息
            $novel = Novel::where(['site_id' => 0, 'source_id' => $source_id])->first();
            if (empty($novel)) {
                $response = $this->getJson(sprintf($info_url, $sub_dir, $source_id))->data;
                if (empty($response)) {
                    continue;
                }
                $novel = Novel::create([
                    'title' => $response->Name,
                    'img' => $response->Img,
                    'author' => $response->Author,
                    'content' => trim($response->Desc),
                    'serialize' => $response->BookStatus,
                    'favorites' => 0,
                    'total_score' => $response->BookVote->TotalScore,
                    'voter_count' => $response->BookVote->VoterCount,
                    'score' => $response->BookVote->Score,
                    'site_id' => 0,
                    'source_id' => $source_id,
                    'updated_at' => strtotime($response->LastTime)
                ]);
            }

            // 章节例表
            $ret_arr = $this->getJson(sprintf($index_url, $sub_dir, $novel->source_id));

            // URL例表
            foreach ($ret_arr->data->list as $rows) {
                foreach ($rows->list as $row) {
                    $urls[] = sprintf($read_url, $sub_dir, $source_id, $row->id);
                }
            }
            // 开始采集
            $ql->curlMulti($urls)
                ->success(function (QueryList $ql, CurlMulti $curl, $r) {
                    echo "Current url:{$r['info']['url']} \r\n";
                    $json = $r['body'];
                    $ret_json = trim($json, "\xEF\xBB\xBF"); // 去掉BOM头信息
                    $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                    dd($ret_json);
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
            $i++;
        }
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
            if (count($data) == 0) {
                exit('结束了');
            }
            // URL例表
            $urls = [];
            foreach ($data as $row) {
                $last_id = $row->id;
                if (empty($row['title'])) {
//                    continue;
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
                            $chunk_limit = count($add);
                            if ($chunk_limit > 1000) {
                                // 分组执行
                                $chunk_result = array_chunk($add, 1000);
                                foreach ($chunk_result as $row) {
                                    Chapter::insert($row);
                                }
                            } else {
                                Chapter::insert($add);
                            }
                            unset($add);
                        }
                    }

                    $count = Chapter::where(['novel_id' => $novel_id, 'chapter_order' => 0])->count();
                    Novel::where('id', $novel_id)->update(['chapter_count' => $count, 'display' => 1]);
                    echo PHP_EOL;
                }

                // 释放资源
                $ql->destruct();
            })->start([
                'maxThread' => 10,
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
