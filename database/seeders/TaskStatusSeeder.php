<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Создана', 'code' => 'created', 'color' => '#6c757d'],
            ['name' => 'В работе', 'code' => 'in_progress', 'color' => '#0d6efd'],
            ['name' => 'На проверке', 'code' => 'review', 'color' => '#ffc107'],
            ['name' => 'Завершена', 'code' => 'done', 'color' => '#198754'],
            ['name' => 'Отменена', 'code' => 'cancelled', 'color' => '#dc3545'],
        ];

        foreach ($statuses as $status) {
            TaskStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}