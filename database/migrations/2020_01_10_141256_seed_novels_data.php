<?php

use App\Models\Novel;
use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use QL\Ext\CurlMulti;
use QL\QueryList;

class SeedNovelsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // 引入多线程插件
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class);
        //or Custom function name
        $ql->use(CurlMulti::class, 'curlMulti');

        $url = "http://downbak.hzwip.com/BookFiles/Html/%d/%d/info.html";

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
                $response = $response->data;
                $category = Category::where('caption', '=', $response->CName)->first();
                $data = [
                    'category_id' => $category->id,
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
                    'source_id' => $response->Id,
                    'updated_at' => strtotime($response->LastTime)
                ];
                print_r($data);
                $novel = Novel::create($data);
                echo $novel->id . "\t";
                echo strtotime($response->LastTime);
                echo PHP_EOL;
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
