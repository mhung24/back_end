<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Article extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'image_url',
        'status',
        'priority',
        'views_count',
        'published_at',
        'moderator_note'
    ];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
