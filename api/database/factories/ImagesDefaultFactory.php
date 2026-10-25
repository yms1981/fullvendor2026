<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImagesDefault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImagesDefault>
 */
final class ImagesDefaultFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ImagesDefault::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
        ];
    }
}
