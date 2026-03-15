<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'message_id',
        'user_id',
        'original_name',
        'file_name',
        'mime_type',
        'file_size',
        'path',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}