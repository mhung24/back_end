<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleReviewed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $article;
    public $message;

    public function __construct($article, $message)
    {
        $this->article = $article;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('author.' . $this->article->author_id);
    }

    public function broadcastAs()
    {
        return 'article-status-updated';
    }
}
