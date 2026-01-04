<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $article;

    public function __construct($article)
    {
        $this->article = $article;
    }

    public function broadcastOn()
    {
        // Gửi vào một channel chung dành cho các Moderator
        return new Channel('moderator-notifications');
    }

    public function broadcastAs()
    {
        return 'new-article-submitted';
    }
}
