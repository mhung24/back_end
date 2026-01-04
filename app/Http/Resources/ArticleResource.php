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
            'views_count' => $this->views_count ?? 0,
            'earnings' => $this->earnings ?? 0,
            'comment_count' => $this->comment_count ?? 0,
            'created_at' => $this->created_at->format('d/m/Y H:i'),
            'created_at_human' => $this->created_at->diffForHumans(),

            'category' => [
                'id' => $this->category->id ?? null,
                'name' => $this->category->name ?? 'Chưa phân loại',
            ],

            'author' => [
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
        ];
    }
}
