<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ], [
            'name.unique' => 'Tên danh mục này đã tồn tại trên hệ thống.'
        ]);

        try {
            $category = Category::create([
                'name' => $request->name,
                'slug' => \Illuminate\Support\Str::slug($request->name)
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'Tạo danh mục thành công',
                'data' => $category
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi server: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }
        return response()->json($category, 200);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => 404,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        try {
            $category->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Cập nhật thành công',
                'data' => $category
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi: ' . $th->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => 404,
                'message' => 'Không tìm thấy danh mục'
            ], 404);
        }

        try {
            $category->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Xóa thành công'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi: ' . $th->getMessage()
            ], 500);
        }
    }
}
