<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\NovelRequest;
use App\Http\Resources\NovelResource;
use App\Models\Novel;
use App\Models\Volume;
use App\Mongodb;
use Illuminate\Http\Request;

class NovelsController extends Controller
{
    public function store(NovelRequest $request, Novel $novel, Volume $volume)
    {
        $novel->fill($request->all());
        $novel->save();
        $novel_id = $novel->id;
        $chapters = $request->chapters;
        if ($chapters) {
            $chapters_json = trim($chapters, "\xEF\xBB\xBF"); // 去掉BOM头信息
            $chapters_json = preg_replace('/,\s*([\]}])/m', '$1', $chapters_json); // 修正不规则json
            $chapters_array = json_decode($chapters_json, true);
            if (is_array($chapters_array)) {
                $chapters_list = $chapters_array['data']['list'];
                foreach ($chapters_list as $values) {
                    // 卷名称
                    $volume_id = 0;
                    if ($values['name']) {
                        $volume = Volume::firstOrCreate(['novel_id' => $novel_id, 'name' => $values['name']]);
                        $volume_id = $volume->id;
                    }
                    // 章节
                    $chapters_data = [];
                    foreach ($values['list'] as $val) {
                        $chapters_data[] = ['novel_id' => $novel_id, 'volume_id' => $volume_id, 'title' => $val['name'], 'has_content' => $val['hasContent']];
                    }
                    if ($chapters_data) {
                        Mongodb::connectionMongodb('chapters', $chapters_data);
                        Chapter::insert($chapters_data);
                    }
                }
            }
        }
        return new NovelResource($novel);
    }
}
