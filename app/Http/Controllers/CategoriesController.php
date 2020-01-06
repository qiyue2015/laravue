<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoriesController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(Category::all());
    }

    public function show($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        return new CategoryResource($category);
    }
}
