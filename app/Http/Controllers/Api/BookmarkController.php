<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function toggle(Request $request, $articleId)
    {
        $user = Auth::user();
        $article = Article::findOrFail($articleId);

        $bookmark = $user->bookmarkedArticles()->where('article_id', $articleId)->first();

        if ($bookmark) {
            $user->bookmarkedArticles()->detach($articleId);
            return response()->json([
                'status' => 200,
                'is_bookmarked' => false,
                'message' => 'Đã bỏ lưu bài viết'
            ]);
        }

        $user->bookmarkedArticles()->attach($articleId);
        return response()->json([
            'status' => 200,
            'is_bookmarked' => true,
            'message' => 'Đã lưu bài viết thành công'
        ]);
    }
    public function index()
    {
        $user = Auth::user();

        // Lấy danh sách bài viết kèm theo thông tin tác giả và danh mục để hiển thị ở Frontend
        $bookmarks = $user->bookmarkedArticles()
            ->with(['author', 'category'])
            ->orderBy('bookmarks.created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 200,
            'data' => $bookmarks,
            'message' => 'Lấy danh sách bài viết đã lưu thành công'
        ]);
    }


}
