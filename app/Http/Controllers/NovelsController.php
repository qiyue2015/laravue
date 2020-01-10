<?php

namespace App\Http\Controllers;

use App\Http\Resources\NovelResource;
use App\Models\Novel;

class NovelsController extends Controller
{
    public function index($novel_id)
    {
        $novel = Novel::find($novel_id);
        $chapters = [];
        if ($novel->chapters) {
            foreach ($novel->chapters as $chapter) {
                $volume_index = $chapter->volume_id;
                if ($volume_index == 0) {
                    $chapters[$chapter->id]['name'] = $chapter->chapter_name;
                }
                if ($volume_index != 0) {
                    $chapters[$volume_index]['list'][] = $chapter;
                }
            }
        }
        $novel->chapters = $chapters;
        return new NovelResource($novel);
    }
}
