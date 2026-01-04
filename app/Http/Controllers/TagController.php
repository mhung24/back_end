<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        return response()->json(Tag::all(), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name',
        ]);

        try {
            $tag = Tag::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'Tạo thẻ thành công',
                'data' => $tag
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi: ' . $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json(['message' => 'Không tìm thấy thẻ'], 404);
        }
        return response()->json($tag, 200);
    }

    public function update(Request $request, string $id)
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json(['message' => 'Không tìm thấy thẻ'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name,' . $id,
        ]);

        try {
            $tag->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Cập nhật thành công',
                'data' => $tag
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
        $tag = Tag::find($id);
        if (!$tag) {
            return response()->json(['message' => 'Không tìm thấy thẻ'], 404);
        }

        try {
            $tag->delete();
            return response()->json(['message' => 'Xóa thành công'], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi: ' . $th->getMessage()
            ], 500);
        }
    }
}
