<?php

namespace App\Http\Controllers\Api\Moderator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Article;
use App\Models\Category;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pusher\PushNotifications\PushNotifications;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            return response()->json([
                'total_users' => User::where('role', 'author')->count(),
                'pending_articles' => Article::where('status', 'pending')->count(),
                'approved_articles' => Article::where('status', 'published')->count(),
                'total_views' => Article::sum('views') ?? 0,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getPendingArticles(Request $request)
    {
        try {
            $query = Article::with(['author', 'category'])
                ->where('status', 'pending');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhereHas('author', function ($aq) use ($search) {
                            $aq->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $articles = $query->orderBy('created_at', 'desc')->get();
            $categories = Category::all(['id', 'name']);

            return response()->json([
                'data' => $articles,
                'categories' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getArticleDetail($id)
    {
        try {
            $article = Article::with(['author', 'category', 'tags'])->findOrFail($id);

            return response()->json([
                'status' => 200,
                'data' => $article
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Không tìm thấy bài viết hoặc bài viết đã bị xóa'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateArticleStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $article = Article::findOrFail($id);

            $article->update([
                'status' => $request->status,
                'moderator_note' => $request->review_note ?? '',
                'published_at' => $request->status === 'published' ? now() : $article->published_at
            ]);

            $statusMessages = [
                'published' => 'đã được duyệt xuất bản',
                'rejected' => 'đã bị từ chối',
                'repair' => 'cần được sửa chữa lại'
            ];

            $statusText = $statusMessages[$request->status] ?? 'đã thay đổi trạng thái';
            $message = "Bài viết '{$article->title}' của bạn {$statusText}.";

            Notification::create([
                'recipient_id' => $article->author_id,
                'actor_id' => auth()->id(),
                'type' => 'article_review',
                'entity_id' => $article->id,
                'title' => 'Cập nhật trạng thái bài viết',
                'message' => $message,
                'is_read' => false,
            ]);

            if (env('PUSHER_BEAMS_INSTANCE_ID')) {
                try {
                    $beamsClient = new PushNotifications([
                        "instanceId" => env('PUSHER_BEAMS_INSTANCE_ID'),
                        "secretKey" => env('PUSHER_BEAMS_SECRET_KEY'),
                    ]);

                    $beamsClient->publishToInterests(
                        ["author-{$article->author_id}"],
                        [
                            "web" => [
                                "notification" => [
                                    "title" => "Cập nhật bài viết",
                                    "body" => $message,
                                    "deep_link" => "http://localhost:5173/author/my-articles",
                                ]
                            ]
                        ]
                    );
                } catch (\Exception $e) {
                    \Log::error("Pusher Beams Error: " . $e->getMessage());
                }
            }

            DB::commit();
            return response()->json(['message' => 'Thành công'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $totalApproved = Article::where('status', 'published')->count();
            $totalRejected = Article::where('status', 'rejected')->count();
            $totalPending = Article::where('status', 'pending')->count();

            $monthlyData = Article::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw("SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published"),
                DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            )
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')
                ->get();

            $performance = array_fill(0, 12, ['published' => 0, 'rejected' => 0]);
            foreach ($monthlyData as $data) {
                $performance[$data->month - 1] = [
                    'published' => (int) $data->published,
                    'rejected' => (int) $data->rejected
                ];
            }

            $topAuthors = User::where('role', 'author')
                ->withCount(['articles as total_articles'])
                ->withCount([
                    'articles as approved_articles' => function ($query) {
                        $query->where('status', 'published');
                    }
                ])
                ->orderBy('total_articles', 'desc')
                ->take(4)
                ->get()
                ->map(function ($user) {
                    $accuracy = $user->total_articles > 0
                        ? round(($user->approved_articles / $user->total_articles) * 100)
                        : 0;
                    return [
                        'name' => $user->name,
                        'articles' => $user->total_articles,
                        'accuracy' => $accuracy . '%',
                        'status' => $accuracy >= 90 ? 'Tốt' : ($accuracy >= 70 ? 'Ổn định' : 'Chú ý'),
                        'avatar_seed' => $user->id
                    ];
                });

            return response()->json([
                'stats' => [
                    'approved_total' => number_format($totalApproved),
                    'rejected_total' => number_format($totalRejected),
                    'pending_total' => number_format($totalPending),
                    'reports_total' => 0,
                ],
                'monthly_performance' => $performance,
                'top_authors' => $topAuthors
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function getReportData(Request $request)
    {
        try {
            $period = $request->query('period', 'day');
            $query = Article::with(['author', 'category']);

            switch ($period) {
                case 'month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'quarter':
                    $query->whereBetween('created_at', [now()->startOfQuarter(), now()->endOfQuarter()]);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
                default:
                    $query->whereDate('created_at', now()->today());
                    break;
            }

            return response()->json(['data' => $query->get()], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
