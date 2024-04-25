<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatsImage extends Model
{
    use HasFactory;
    protected $table = 'chats_images';
    protected $fillable = ['chat_id', 'chat_session_id', 'image_location', 'status'];
}
