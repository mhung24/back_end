<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = ['follower_id', 'author_id'];

    protected $fillable = [
        'follower_id',
        'author_id',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected function setKeysForSaveQuery($query)
    {
        $query->where('follower_id', $this->getAttribute('follower_id'))
            ->where('author_id', $this->getAttribute('author_id'));
        return $query;
    }
}
