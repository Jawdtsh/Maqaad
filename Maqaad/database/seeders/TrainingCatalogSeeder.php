<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingCatalogSeeder extends Seeder
{
    private const CATALOG = [
        [
            'center' => [
                'name' => 'Damascus Training Center',
                'commission_rate' => '10.00',
            ],
            'branch' => [
                'name' => 'Damascus Branch',
                'city' => 'Damascus',
                'timezone' => 'Asia/Damascus',
            ],
            'course' => [
                'title' => 'Laravel Fundamentals',
                'duration_minutes' => 120,
            ],
            'sessions' => [
                ['hour' => 10, 'price' => '50.00'],
                ['hour' => 11, 'price' => '60.00'],
            ],
        ],
        [
            'center' => [
                'name' => 'Muscat Training Center',
                'commission_rate' => '15.00',
            ],
            'branch' => [
                'name' => 'Muscat Branch',
                'city' => 'Muscat',
                'timezone' => 'Asia/Muscat',
            ],
            'course' => [
                'title' => 'Database Fundamentals',
                'duration_minutes' => 90,
            ],
            'sessions' => [
                ['hour' => 10, 'price' => '40.00'],
                ['hour' => 13, 'price' => '45.00'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $data) {
            $this->seedCenter($data);
        }
    }

    private function seedCenter(array $data): void
    {
        $centerId = $this->findOrCreateId(
            'centers',
            ['name' => $data['center']['name']],
            [
                'commission_rate' => $data['center']['commission_rate'],
                'created_at' => now(),
            ]
        );

        $branch = $data['branch'];

        $branchId = $this->findOrCreateId(
            'branches',
            [
                'center_id' => $centerId,
                'name' => $branch['name'],
            ],
            [
                'city' => $branch['city'],
                'timezone' => $branch['timezone'],
            ]
        );

        $course = $data['course'];

        $courseId = $this->findOrCreateId(
            'courses',
            [
                'center_id' => $centerId,
                'title' => $course['title'],
            ],
            [
                'duration_minutes' => $course['duration_minutes'],
            ]
        );

        if (DB::table('sessions')
            ->where('course_id', $courseId)
            ->where('branch_id', $branchId)
            ->exists()) {
            return;
        }

        $date = CarbonImmutable::now($branch['timezone'])
            ->addDays(14);

        foreach ($data['sessions'] as $item) {
            $start = $date->setTime($item['hour'], 0);

            DB::table('sessions')->insert([
                'center_id' => $centerId,
                'course_id' => $courseId,
                'branch_id' => $branchId,
                'starts_at' => $start->utc()->toIso8601String(),
                'ends_at' => $start
                    ->addMinutes($course['duration_minutes'])
                    ->utc()
                    ->toIso8601String(),
                'price' => $item['price'],
                'capacity' => 10,
                'status' => 'scheduled',
            ]);
        }
    }

    private function findOrCreateId(
        string $table,
        array $lookup,
        array $attributes
    ): int {
        $id = DB::table($table)
            ->where($lookup)
            ->value('id');

        return $id !== null
            ? (int) $id
            : (int) DB::table($table)->insertGetId([
                ...$lookup,
                ...$attributes,
            ]);
    }
}