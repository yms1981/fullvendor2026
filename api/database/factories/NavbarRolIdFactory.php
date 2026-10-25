<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Navbar;
use App\Models\NavbarRolId;
use App\Models\ManagerProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\NavbarRolId>
 */
final class NavbarRolIdFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = NavbarRolId::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'navbar_id' => Navbar::factory(),
            'rol_id' => ManagerProfiles::factory(),
            'company_id' => Company::factory(),
            'fhater_id' => fake()->optional()->randomNumber(),
        ];
    }
}
