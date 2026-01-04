<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Article;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Pusher\PushNotifications\PushNotifications;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $articles = Article::with(['tags', 'category', 'author'])
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('title', 'like', '%' . $keyword . '%');
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($limit);

        $skip = ($articles->currentPage() - 1) * $articles->perPage();

        return response()->json([
            'status' => 200,
            'message' => 'Lấy danh sách thành công',
            'data' => ArticleResource::collection($articles->items()),
            'pagination' => [
                'total' => $articles->total(),
                'limit' => $articles->perPage(),
                'skip' => $skip,
                'page' => $articles->currentPage(),
            ]
        ], 200);
    }

    public function store(StoreArticleRequest $request)
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            $article = Article::create([
                'author_id' => auth()->id(),
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
                'summary' => $validated['summary'] ?? null,
                'content' => $request->input('content'),
                'image_url' => $validated['image_url'] ?? null,
                'status' => 'pending',
            ]);

            // Lưu thông báo vào database cho tác giả
            \App\Models\Notification::create([
                'recipient_id' => $request->user()->id,
                'actor_id' => $request->user()->id,
                'type' => 'article_created',
                'entity_id' => $article->id,
                'title' => 'Gửi bài thành công',
                'message' => "Bài viết '{$article->title}' đã được gửi duyệt.",
                'is_read' => false,
            ]);

            if (!empty($validated['tags'])) {
                $article->tags()->attach($validated['tags']);
            }

            // --- 1. NHẢY SỐ SIDEBAR (Pusher Channels) ---
            // Tạo Event này bằng lệnh: php artisan make:event ArticleSubmitted
            // broadcast(new \App\Events\ArticleSubmitted($article))->toOthers();

            // --- 2. GỬI THÔNG BÁO POPUP (Pusher Beams) ---
            try {
                $beamsClient = new PushNotifications([
                    "instanceId" => env('PUSHER_BEAMS_INSTANCE_ID'),
                    "secretKey" => env('PUSHER_BEAMS_SECRET_KEY'),
                ]);

                $beamsClient->publishToInterests(
                    ["moderators"],
                    [
                        "web" => [
                            "notification" => [
                                "title" => "Bài viết mới chờ duyệt",
                                "body" => "Tác giả {$request->user()->name} vừa gửi bài: '{$article->title}'",
                                "deep_link" => "http://localhost:5173/moderator/moderation",
                            ]
                        ]
                    ]
                );
            } catch (\Exception $e) {
                \Log::error("Pusher Beams Error: " . $e->getMessage());
            }

            DB::commit();
            return new ArticleResource($article->load(['tags', 'category', 'author']));

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi server: ' . $th->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $article = Article::with(['tags', 'category', 'author'])->find($id);

        if (!$article) {
            return response()->json(['message' => 'Không tìm thấy bài viết'], 404);
        }

        return new ArticleResource($article);
    }

    public function update(UpdateArticleRequest $request, string $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Không tìm thấy bài viết'], 404);
        }

        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $slug = $article->slug;
            if (isset($validated['title']) && $validated['title'] !== $article->title) {
                $slug = Str::slug($validated['title']) . '-' . Str::random(5);
            }

            $article->update([
                'category_id' => $validated['category_id'] ?? $article->category_id,
                'title' => $validated['title'] ?? $article->title,
                'slug' => $slug,
                'summary' => $validated['summary'] ?? $article->summary,
                'content' => $request->input('content') ?? $article->content,
                'image_url' => $validated['image_url'] ?? $article->image_url,
                'status' => $validated['status'] ?? $article->status,
            ]);

            if (isset($validated['tags'])) {
                $article->tags()->sync($validated['tags']);
            }

            DB::commit();
            return new ArticleResource($article->load(['tags', 'category', 'author']));

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi server: ' . $th->getMessage()], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Bài viết không tồn tại'], 404);
        }

        try {
            $article->delete();
            return response()->json(['message' => 'Xóa bài viết thành công'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Lỗi server: ' . $th->getMessage()], 500);
        }
    }

    public function mine(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['status' => 401, 'message' => 'Chưa đăng nhập'], 401);
            }

            $articles = Article::with('category')
                ->where('author_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('limit', 10));

            return response()->json([
                'status' => 200,
                'message' => 'Lấy danh sách bài viết cá nhân thành công',
                'data' => $articles
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 500,
                'message' => 'Lỗi máy chủ: ' . $th->getMessage()
            ], 500);
        }
    }
}
