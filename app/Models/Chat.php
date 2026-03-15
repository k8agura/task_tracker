<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}