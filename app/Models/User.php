<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bio',
        'avatar',
        'phone',
        'location',
        'website',
        'reputation_score',
        'years_of_experience',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function bookmarkedArticles()
    {
        return $this->belongsToMany(Article::class, 'bookmarks', 'user_id', 'article_id')->withTimestamps();
    }

    // Quan hệ lấy những người đang follow mình (Fans)
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'author_id', 'follower_id')->withTimestamps();
    }

    // Quan hệ lấy những người mình đang follow (Idols)
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'author_id')->withTimestamps();
    }

    // Quan hệ lấy các báo cáo mình đã gửi
    public function reportsMade()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    // Quan hệ lấy các báo cáo mình bị dính phải
    public function reportsReceived()
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
