<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status_id',
        'creator_id',
        'due_date',
        'parent_task_id',
        'completed_by',
        'completed_at',
        'completion_report',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function performers()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function histories()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function chat()
    {
        return $this->hasOne(Chat::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function childTasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}