<?php

namespace App\Http\Controllers\Api; // Dòng này cực kỳ quan trọng

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $articleId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        $article = Article::findOrFail($articleId);

        $comment = Comment::create([
            'article_id' => $article->id,
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'parent_id' => $request->input('parent_id'),
            'status' => 'visible',
        ]);

        return response()->json([
            'status' => 200,
            'data' => $comment->load('user'),
            'message' => 'Cảm ơn bạn đã chia sẻ ý kiến!'
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $comment = Comment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $comment->update(['content' => $request->input('content')]);

        return response()->json([
            'status' => 200,
            'data' => $comment->load('user'),
            'message' => 'Bình luận đã được cập nhật'
        ]);
    }

    public function destroy($id)
    {
        $comment = Comment::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $comment->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Đã xóa bình luận thành công'
        ]);
    }
}
