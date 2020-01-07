<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Http\Request;

class SearchsController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim($request->keyword);
        if (!empty($keyword)) {
            return Novel::where('title', '=', $keyword)->limit(10)->get();
        }
    }
}
