<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = ['follower_id', 'author_id'];

    protected $fillable = [
        'follower_id',
        'author_id',
    ];
}
