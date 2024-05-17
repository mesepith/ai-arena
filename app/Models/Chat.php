<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_message', 'ai_response', 'chat_session_id', 'ai_model', 'service_by', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'status', 'has_image'
    ];

    public function images()
    {
        return $this->hasMany(ChatsImage::class, 'chat_id');
    }
}
