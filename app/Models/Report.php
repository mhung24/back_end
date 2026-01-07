<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Thêm dòng này

class Report extends Model
{
    use HasUuids; // Sử dụng trait này

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'type',
        'priority',
        'description',
        'status',
        'moderator_note'
    ];

    // Quan hệ với người báo cáo
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // Quan hệ với người bị báo cáo
    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
