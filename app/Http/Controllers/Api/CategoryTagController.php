<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;

class CategoryTagController extends Controller
{
    public function index()
    {
        return response()->json([
            'categories' => Category::all(),
            'tags' => Tag::all()
        ], 200);
    }
}
