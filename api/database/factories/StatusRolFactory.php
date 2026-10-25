<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ManagerProfiles;
use App\Models\StatusRol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StatusRol>
 */
final class StatusRolFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = StatusRol::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'rol_id' => ManagerProfiles::factory(),
            'created_at' => fake()->optional()->dateTime(),
            'update_at' => fake()->optional()->dateTime(),
            'id_status' => fake()->optional()->randomNumber(),
        ];
    }
}
