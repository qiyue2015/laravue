<?php

use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use QL\Ext\CurlMulti;
use QL\QueryList;

class SeedChaptersData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        /*
        // 引入多线程插件
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class);
        //or Custom function name
        $ql->use(CurlMulti::class, 'curlMulti');

        $url = "http://downbak.hzwip.com/BookFiles/Html/%d/%d/index.html";

        $i = 1;
        $urls = [];
        for ($i = 1; $i <= 10; $i++) {
            $source_id = $i;
            $sub_dir = intval($source_id / 1000) + 1;
            $urls[] = sprintf($url, $sub_dir, $source_id);
        }

        $ql->curlMulti($urls)
            ->success(function (QueryList $ql, CurlMulti $curl, $r) {
                $json = $r['body'];
                $ret_json = trim($json, "\xEF\xBB\xBF"); // 去掉BOM头信息
                $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                $response = json_decode($ret_json);
                $novel_id = $response->data->id;
                $response = $response->data;
                $chapters_array = $response->list;
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
                    $volume_name = trim($list->name);
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
                    foreach ($list->list as $item) {
                        $data['volume_id'] = $volume->id;
                        $data['chapter_name'] = trim($item->name);
                        $data['chapter_type'] = 0;
                        $data['has_content'] = intval($item->hasContent);
                        $data['source_chapter_id'] = intval($item->id);
                        $add[] = $data;
                    }
                    // 分割数组
                    $chunk_result = array_chunk($add, 2000);
                    foreach ($chunk_result as $result) {
                        Chapter::insert($result);
                    }
                    unset($add);
                }
                $count = Chapter::where('novel_id', $novel_id)->where('chapter_type', 0)->count();
                Novel::where('id', $novel_id)->update(['display' => 1, 'chapter_count' => $count]);
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
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
