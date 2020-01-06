<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class xiaoshuo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:upload';

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
        $x = 1;
        while ($x <= 506) {
            echo PHP_EOL . $x . ' >>>' . PHP_EOL;
            $data = Novel::where('display', 0)->limit(1000)->get(['id', 'title', 'source_id']);
            if (!empty($data)) {
                foreach ($data as $row) {
                    $novel_id = $row->id;

                    echo $novel_id . "\t";

                    if (empty($row['title'])) {
                        continue;
                    }

                    // 获取章节
                    $url = 'http://iosapp.jiaston.com/book/' . $row['source_id'] . '/';
                    $html = file_get_contents($url);
                    $ret_json = trim($html, "\xEF\xBB\xBF"); // 去掉BOM头信息
                    $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                    $ret_arr = json_decode($ret_json, true);
                    if (empty($ret_arr)) {
                        continue;
                    }

                    // 章节例表
                    $list = $ret_arr['data']['list'];
                    if (empty($list)) {
                        continue;
                    }

                    $chapter = serialize(json_encode($list, JSON_UNESCAPED_UNICODE));
                    Novel::where('id', $novel_id)->update(['chapters' => $chapter, 'display' => 1]);

//                    // 所有分卷
//                    $volume_list = DB::table('chapters_0')
//                        ->select('id', 'chapter_name')
//                        ->where('novel_id', '=', $row['id'])
//                        ->get();
//
//                    foreach ($list as $value) {
//                        $volume_id = 0;
//                        $volume_name = trim($value['name']);
//                        $data = [
//                            'novel_id' => $row['id'],
//                            'volume_id' => 0,
//                            'chapter_name' => '',
//                            'chapter_type' => 0,
//                            'chapter_order' => 0,
//                            'source_chapter_id' => 0,
//                            'display' => 1,
//                            'has_content' => 0,
//                            'created_at' => time(),
//                            'updated_at' => time()
//                        ];
//
//                        if (!empty($volume_list)) {
//                            foreach ($volume_list as $valume) {
//                                if ($valume->chapter_name == $volume_name) {
//                                    $volume_id = $valume->id;
//                                    break;
//                                }
//                            }
//                        }
//                        if (empty($volume_id)) {
//                            $volume = $data;
//                            $volume['chapter_type'] = 1;
//                            $volume['chapter_name'] = $value['name'];
//                            $volume['has_content'] = 0;
//                            $volume_id = DB::table('chapters_0')->insertGetId($volume);
//                        }
//
//                        $add = [];
//                        foreach ($value['list'] as $item) {
//                            $data['volume_id'] = $volume_id;
//                            $data['chapter_name'] = trim($item['name']);
//                            $data['has_content'] = intval($item['hasContent']);
//                            $add[] = $data;
//                            echo '.';
//                        }
//                        DB::table('chapters_0')->insert($add);
//                        unset($add);
//                    }
//
//                    $count = DB::table('chapters_0')
//                        ->where(['novel_id' => $row['id'], 'chapter_order' => 0])
//                        ->count();
//                    Novel::where('id', $row['id'])
//                        ->update(['chapter_count' => $count, 'display' => 1]);
                }
            } else {
                echo PHP_EOL . '-------------------- empty ' . $x . '-------------------- ' . PHP_EOL;
            }
            $x++;
            echo PHP_EOL;
        }
        exit();
        $x = 1;
        while ($x <= 940) {
            echo $x . ' --------------';
            echo PHP_EOL;
            $data = Novel::where('display', 0)->where('id', '>', 35913)->limit(100)->get(['id', 'source_id'])->toArray();
            if (is_array($data)) {
                foreach ($data as $row) {
                    echo $row['id'];
                    echo "\t";
                    $url = 'http://iosapp.jiaston.com/info/' . $row['source_id'] . '.html';
                    $html = file_get_contents($url);
                    $ret_json = trim($html, "\xEF\xBB\xBF"); // 去掉BOM头信息
                    $ret_json = preg_replace('/,\s*([\]}])/m', '$1', $ret_json); // 修正不规则json
                    $ret_arr = json_decode($ret_json, true);
                    if (empty($ret_arr)) {
                        continue;
                    }

                    $item = $ret_arr['data'];
                    $category = Category::where(['caption' => trim($item['CName'])])->first();
                    if (empty($category)) {
                        print_r($category);
                        print_r($item);
                        exit();
                    }
                    $data = [
                        'category_id' => intval($category->id),
                        'title' => $item['Name'],
                        'author' => $item['Author'],
                        'content' => trim($item['Desc']),
                        'serialize' => $item['BookStatus'] == '连载' ? 1 : 0,
                        'total_score' => $item['BookVote']['TotalScore'],
                        'voter_count' => $item['BookVote']['VoterCount'],
                        'score' => $item['BookVote']['Score'],
                        'display' => 1,
                    ];
                    Novel::where('id', $row['id'])->update($data);
                }
            }
            $x++;
            echo PHP_EOL;
        }
        exit();
        $x = 1;
        while ($x <= 10) {
            $item = Novel::find($x);

            if (empty($item->title)) {
                continue;
            }

            // 处理作者
            $author_name = trim($item->author);
            if ($author_name && empty($item->author_id)) {
                $author_id = (int)Cache::get($author_name);
                if (empty($author_id)) {
                    $author = Author::firstOrCreate(['name' => trim($item->author)]);
                    $author_id = $author->id;
                    Cache::add($author_name, $author_id, 7200);
                }
                Novel::where('id', $item->id)->update(['author_id' => $author_id]);
            }

            if (empty($item->chapters)) {
                continue;
            }

            // 处理章节
            $chapters = unserialize($item->chapters);
            $chapters_json = trim($chapters, "\xEF\xBB\xBF"); // 去掉BOM头信息
            $chapters_json = preg_replace('/,\s*([\]}])/m', '$1', $chapters_json); // 修正不规则json
            $chapters_array = json_decode($chapters_json, true);
            print_r($chapters_array['data']);
            exit();
            $chapters = json_encode($chapters_array['data']['list'], JSON_UNESCAPED_UNICODE);
            $chapters = $chapters_array;
            exit($chapters);
            if (is_array($chapters_array)) {
                print_r($chapters_array);
                foreach ($chapters_array as $row) {
                    print_r($row);
                    exit();
                }
            }
            exit();
        }
        exit();
        $x = 100001;
        while ($x <= 500000) {
            $add = [];
            for ($i = 0; $i < 1000; $i++) {
                $source_id = $x;
                $add[] = [
                    'site_id' => 0,
                    'content' => '',
                    'chapters' => '',
                    'source_id' => $source_id,
                    'created_at' => time(),
                    'updated_at' => time()
                ];
                $x++;
                echo $source_id . "\t";
            }
            Novel::insert($add);
        }
        exit();
        $x = 111;
        while ($x < 50110) {
            $item = DB::table('test.book_01')->find($x);
            if (empty($item->chapters)) {
                continue;
            }
            $chapters = unserialize($item->chapters);
            $chapters_json = trim($chapters, "\xEF\xBB\xBF"); // 去掉BOM头信息
            $chapters_json = preg_replace('/,\s*([\]}])/m', '$1', $chapters_json); // 修正不规则json
            $chapters_array = json_decode($chapters_json, true);
            $chapters = json_encode($chapters_array['data']['list'], JSON_UNESCAPED_UNICODE);

            $data = [
                'title' => $item->title,
                'author' => $item->author,
                'content' => $item->description,
                'total_score' => $item->total_score,
                'voter_count' => $item->voter_count,
                'chapters' => serialize($chapters)
            ];
            DB::table('novels')->where('id', $item->source_id)->update($data);
            exit(3);
        }
//        $x = 395561;
//        while ($x <= 500000) {
//
//            $source_id = $x;
//
//            $add = [
//                'site_id' => 0,
//                'content' => '',
//                'chapters' => '',
//                'source_id' => $source_id,
//                'created_at' => date('Y-m-d H:i:s', time())
//            ];
//            Novel::insert($add);
//            echo $source_id . "\t";
//            $x++;
//        }
    }
}
