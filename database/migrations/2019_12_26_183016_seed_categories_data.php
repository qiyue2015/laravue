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
        // 男生
        $str_arr1 = ['玄幻奇幻', '东方玄幻', '都市现代', '历史军事', '网游竞技', '恐怖科幻', '同人次元', '都市言情', '浪漫青春', '现代言情', '武侠仙侠', '科幻灵异'];
        $str_arr2 = ['玄幻', '修真', '都市', '穿越', '网游', '科幻', '次元', '言情', '青春', '现代', '武侠', '幻想'];
        $str_arr3 = ['xuanhuan', 'xiuzhen', 'dushi', 'chuanyue', 'wangyou', 'kehuan', 'ciyuan', 'yanqing', 'qingchun', 'xiandai', 'gudai', 'huanxiang'];
        $data = [];
        foreach ($str_arr1 as $key => $val) {
            $sort = $key + 1;
            $data[] = [
                'type' => 'male',
                'caption' => $val,
                'shortname' => $str_arr2[$key],
                'pinyin' => $str_arr3[$key],
                'sort' => $sort,
                'status' => 1
            ];
        }
        DB::table('categories')->insert($data);
        unset($data);

        // 女生
        $female_arr1 = ['现代言情', '古代言情', '浪漫青春', '游戏情缘', 'N次元', '科幻空间', '悬疑灵异', '玄幻魔幻', '仙侠武侠'];
        $female_arr2 = ['现代', '古代', '浪漫', '游戏', '次元', '科幻', '悬疑', '玄幻', '仙侠'];
        $female_arr3 = ['xiandai', 'gudai', 'langman', 'youxi', 'ciyuan', 'kehuan', 'xuanyi', 'xuanhuan', 'xianxia'];

        $data = [];
        foreach ($female_arr1 as $key => $val) {
            $sort = $key + 1;
            $data[] = [
                'type' => 'female',
                'caption' => $val,
                'shortname' => $female_arr2[$key],
                'pinyin' => $female_arr3[$key],
                'sort' => $sort,
                'status' => 1
            ];
        }
        DB::table('categories')->insert($data);
        unset($data);
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
