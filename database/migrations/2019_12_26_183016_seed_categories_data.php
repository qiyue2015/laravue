<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SeedCategoriesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $str_arr1 = ['玄幻奇幻', '东方玄幻', '都市现代', '历史军事', '网游竞技', '恐怖科幻', '同人次元', '都市言情', '浪漫青春', '现代言情', '武侠仙侠', '科幻灵异', '女生频道'];
        $str_arr2 = ['玄幻', '修真', '都市', '穿越', '网游', '科幻', '次元', '言情', '青春', '现代', '武侠', '幻想', '女生'];
        $str_arr3 = ['xuanhuan', 'xiuzhen', 'dushi', 'chuanyue', 'wangyou', 'kehuan', 'ciyuan', 'yanqing', 'qingchun', 'xiandai', 'gudai', 'huanxiang', 'nvsheng'];
        $data = [];
        foreach ($str_arr1 as $key => $val) {
            $sort = $key + 1;
            $data[] = [
                'pid' => 0,
                'title' => $val,
                'ftitle' => $str_arr2[$key],
                'pinyin' => $str_arr3[$key],
                'sort' => $sort,
                'status' => 1
            ];
        }
        DB::table('categories')->insert($data);
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
