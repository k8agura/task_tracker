<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'date_from',
        'date_to',
        'filters',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'filters' => 'array',
            'data' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}