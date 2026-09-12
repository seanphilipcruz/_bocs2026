<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $jobId = DB::table('jobs')
            ->where('is_active', 1)
            ->value('id') ?? DB::table('jobs')->value('id');

        $jobId ??= DB::table('jobs')->insertGetId([
            'title' => 'BOCS API Tester',
            'level' => '3',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->updateOrInsert(
            ['email' => 'bocs@rx931.com'],
            [
                'last_name' => 'Developer',
                'first_name' => 'BOCS',
                'middle_name' => null,
                'birthday' => '2000-01-01',
                'nickname' => 'BOCS Developer',
                'color' => '#38bdf8',
                'password' => Hash::make('BOCSDeveloper'),
                'remember_token' => null,
                'job_id' => $jobId,
                'is_active' => 1,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('employees')
            ->where('email', 'bocs@rx931.com')
            ->delete();

        $testJob = DB::table('jobs')->where('title', 'BOCS API Tester')->first();

        if ($testJob && ! DB::table('employees')->where('job_id', $testJob->id)->exists()) {
            DB::table('jobs')->where('id', $testJob->id)->delete();
        }
    }
};
