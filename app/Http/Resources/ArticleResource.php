<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'content' => $this->content,
            'image_url' => $this->image_url,
            'status' => $this->status,
            'views' => $this->views ?? 0,

            // Đảm bảo tên trường này khớp với SocialSidebar và VNDailyDetail (commentCount)
            'comment_count' => $this->comments_count ?? 0,
            'is_bookmarked' => $this->is_bookmarked ?? false,

            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'created_at_human' => $this->created_at->diffForHumans(),

            'category' => [
                'id' => $this->category->id ?? null,
                'name' => $this->category->name ?? 'Chưa phân loại',
            ],

            'author' => [
                'id' => $this->author->id ?? null,
                'name' => $this->author->name ?? 'Unknown',
                'avatar' => $this->author->avatar_url ?? null,
            ],

            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                ];
            }),

            'comments' => $this->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user_id' => $comment->user_id,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'name' => $comment->user->name ?? 'Người dùng',
                        'avatar' => $comment->user->avatar_url ?? null,
                    ]
                ];
            }),
        ];
    }
}
