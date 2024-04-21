<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatsTitle extends Model
{
    use HasFactory;
    protected $table = 'chats_title';
    protected $fillable = ['chat_id', 'chat_session_id', 'title', 'status'];
}
