<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ImportHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImportHistory>
 */
final class ImportHistoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ImportHistory::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'import_date' => fake()->optional()->dateTime(),
            'importedfile' => fake()->word,
            'records_imported' => fake()->randomNumber(),
        ];
    }
}
