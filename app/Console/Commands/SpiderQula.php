<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use QL\Ext\CurlMulti;
use QL\QueryList;
use App\Models\Category;
use App\Models\Novel;


class SpiderQula extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spider:qula';

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

        $info_url = "http://infos.2otea.com/BookFiles/Html/%d/%d/info.html";
        $index_url = "http://infos.2otea.com/BookFiles/Html/%d/%d/index.html";
        $read_url = 'http://infos.2otea.com/BookFiles/Html/%d/%d/%d.html';

        // 引入多线程插件
        $ql = QueryList::getInstance();
        $ql->use(CurlMulti::class);
        //or Custom function name
        $ql->use(CurlMulti::class, 'curlMulti');

        $i = 1;
        $urls = [];
        for ($i = 1; $i <= 10; $i++) {
            $source_id = $i;
            $sub_dir = intval($source_id / 1000) + 1;
            $urls[] = sprintf($info_url, $sub_dir, $source_id);
        }

        $ql->curlMulti($urls)
            ->success(function (QueryList $ql, CurlMulti $curl, $r) {
                $json = $r['body'];
                $ret_json = trim($json, "\xEF\xBB\xBF"); // 去掉BOM头信息
                $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                $response = json_decode($ret_json);
                $response = $response->data;
                $category = Category::where('caption', '=', $response->CName)->first();
                try {
                    $novel = Novel::create([
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
                    ]);
                    dd($novel);
                } catch (\Exception $e) {

                }
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
}
