<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'email',
        'password',
        'position',
        'department_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function fullName(): string
    {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . $this->middle_name);
    }
}