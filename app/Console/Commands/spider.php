<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        $last_id = 39;
        while ($x <= 1) {
            echo "\n\r------------------- Start {$x} > {$last_id} ------------------- \n\r";
            // 每次取 1000
            $data = Novel::where('display', 0)->where('id', '>', $last_id)->orderBy('id', 'asc')->limit(100)->get(['id', 'title', 'source_id', 'chapters']);
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $last_id = $row->id;
                    $novel_id = $row->id;

                    echo "\r\n{$novel_id} Start \r\n";

                    if (empty($row->title) || empty($row->chapters)) {
                        continue;
                    }

                    try {
                        $chapters = unserialize($row->chapters);
                        $chapters = trim($chapters, "\xEF\xBB\xBF"); // 去掉BOM头信息
                        $chapters = preg_replace('/,\s*([\]}])/m', '$1', $chapters); // 修正不规则json
                    } catch (\Exception $e) {
                        continue;
                    }

                    $chapters_array = json_decode($chapters, true);
                    if (empty($chapters_array)) {
                        continue;
                    }
                    if (array_key_exists('status', $chapters_array)) {
                        $chapters_array = $chapters_array['data']['list'];
                    }
                    foreach ($chapters_array as $list) {
                        $data = [
                            'novel_id' => $novel_id,
                            'volume_id' => 0,
                            'chapter_name' => '',
                            'chapter_type' => 0,
                            'chapter_order' => 0,
                            'source_chapter_id' => 0,
                            'display' => 1,
                            'has_content' => 0
                        ];

                        // 分卷
                        $volume = null;
                        $volume_name = trim($list['name']);
                        $volumes = Chapter::where('novel_id', $novel_id)->where('chapter_type', 1)->get();
                        if (!empty($volumes)) {
                            foreach ($volumes as $value) {
                                if ($value->chapter_name == $volume_name) {
                                    $volume = $value;
                                    break;
                                }
                            }
                        }
                        if (empty($volume)) {
                            $data['chapter_type'] = 1;
                            $data['chapter_name'] = $volume_name;
                            $data['has_content'] = 0;
                            $volume = Chapter::create($data);
                        }
                        // 章节
                        $add = [];
                        foreach ($list['list'] as $item) {
                            $source_chapter_key = '$source_chapter_key_' . $item['id'];
                            $source_chapter_id = (int)Cache::get($source_chapter_key);
                            if (empty($source_chapter_id)) {
                                $data['volume_id'] = $volume->id;
                                $data['chapter_name'] = trim($item['name']);
                                $data['chapter_type'] = 0;
                                $data['has_content'] = intval($item['hasContent']);
                                $data['source_chapter_id'] = intval($item['id']);
                                $add[] = $data;
                                echo '.';
//                                $chapter = Chapter::create($data);
//                                if ($chapter) {
//                                    echo '.';
//                                    Cache::add($item['id'], $source_chapter_key, 7200);
//                                }
                            }
                        }
                        // 分割数组
                        $chunk_result = array_chunk($add, 2000);
                        foreach ($chunk_result as $result) {
                            Chapter::insert($result);
                        }
                    }
                    $count = Chapter::where('novel_id', $novel_id)->where('chapter_type', 0)->count();
                    Novel::where('id', $novel_id)->update(['display' => 1, 'chapter_count' => $count]);
                    echo "\r\n{$novel_id} End ({$count}) \r\n\r\n";
                }
            } else {
                $x = 2;
                echo "\n\r\n\r\n\r------------------- ALL OK ------------------- \n\r\n\r\n\r";
            }
        }
    }
}
