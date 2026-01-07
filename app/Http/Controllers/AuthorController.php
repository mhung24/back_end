<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use App\Models\Follow;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthorController extends Controller
{
    public function getProfile($id)
    {
        // Lấy thông tin tác giả và đếm số người theo dõi
        $author = User::where('role', 'author')->findOrFail($id);

        // Đếm followers thủ công hoặc qua relation nếu bạn đã định nghĩa
        $followerCount = DB::table('follows')->where('author_id', $id)->count();

        $articles = Article::where('author_id', $id)
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->get();

        // Kiểm tra xem người dùng hiện tại có đang theo dõi không
        $isFollowing = false;
        if (auth('sanctum')->check()) {
            $isFollowing = DB::table('follows')
                ->where('follower_id', auth('sanctum')->id())
                ->where('author_id', $id)
                ->exists();
        }

        return response()->json([
            'status' => 200,
            'author' => $author,
            'follower_count' => $followerCount,
            'articles' => $articles,
            'is_following' => $isFollowing
        ]);
    }

    public function toggleFollow($id)
    {
        $followerId = auth()->id();

        // Kiểm tra không cho tự follow chính mình
        if ($followerId === $id) {
            return response()->json(['message' => 'Bạn không thể theo dõi chính mình'], 400);
        }

        $follow = DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('author_id', $id);

        if ($follow->exists()) {
            $follow->delete();
            return response()->json(['status' => 200, 'message' => 'unfollowed', 'is_following' => false]);
        }

        DB::table('follows')->insert([
            'follower_id' => $followerId,
            'author_id' => $id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['status' => 200, 'message' => 'followed', 'is_following' => true]);
    }

    public function reportAuthor(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'description' => 'required|string|min:10',
            'priority' => 'string|in:Low,Medium,High,Urgent',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $report = new Report();
        $report->reporter_id = auth()->id();
        $report->reported_user_id = $id;
        $report->type = $request->type;
        $report->priority = $request->priority ?? 'Medium';
        $report->description = $request->description;
        $report->status = 'Pending';
        $report->save();

        return response()->json([
            'status' => 201,
            'message' => 'Báo cáo đã được gửi và đang chờ xử lý.'
        ], 201);
    }
}
