<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => fake()->uuid(),
            'filename' => fake()->word().'.xlsx',
            'total_gtins' => fake()->numberBetween(5, 100),
            'processed_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'success_file_path' => null,
            'failed_file_path' => null,
        ];
    }

    /**
     * Indicate that the batch is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_gtins'] ?? 10;
            $success = fake()->numberBetween(1, $total);
            $failed = $total - $success;

            return [
                'status' => 'completed',
                'processed_count' => $total,
                'success_count' => $success,
                'failed_count' => $failed,
                'started_at' => now()->subMinutes(10),
                'completed_at' => now(),
            ];
        });
    }

    /**
     * Indicate that the batch is processing.
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
                'started_at' => now()->subMinutes(5),
            ];
        });
    }
}
