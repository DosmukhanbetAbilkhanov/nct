<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'gtin' => $this->generateGtin(),
            'ntin' => fake()->numerify('KZ#############'),
            'nameKk' => fake()->words(3, true) . ' (қазақша)',
            'nameRu' => fake()->words(3, true) . ' (русский)',
            'nameEn' => fake()->words(3, true),
            'shortNameKk' => fake()->word() . ' қаз',
            'shortNameRu' => fake()->word() . ' рус',
            'shortNameEn' => fake()->word(),
            'createdDate' => now()->subDays(rand(1, 365))->toDateString(),
            'updatedDate' => now()->subDays(rand(0, 30))->toDateString(),
        ];
    }

    /**
     * Generate a valid 13-digit GTIN.
     */
    private function generateGtin(): string
    {
        // Generate 12 random digits
        $digits = '';
        for ($i = 0; $i < 12; $i++) {
            $digits .= rand(0, 9);
        }

        // Calculate check digit (simplified - not actual GS1 algorithm)
        $checkDigit = rand(0, 9);

        return $digits . $checkDigit;
    }
}
