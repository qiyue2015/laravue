<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Novel;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index()
    {
        $categorys = [];
        foreach (Category::all() as $row) {
            $categorys[$row->type][] = $row;
        }
        return CategoryResource::collection($categorys);
    }

    public function show($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        return new CategoryResource($category);
    }

    public function list(Request $request)
    {
        $result = Novel::where('display', 1)->orderBy('updated_at', 'DESC')->paginate(12);
        return $result;
    }
}
