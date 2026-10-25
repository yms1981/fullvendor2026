<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\CatalogRequest;
use App\Models\CatalogSentMail;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CatalogSentMail>
 */
final class CatalogSentMailFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CatalogSentMail::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'catlog_reqest_id' => CatalogRequest::factory(),
            'hide_price' => fake()->boolean,
            'catalog_id' => Catalog::factory(),
            'catalog_text' => fake()->optional()->word,
            'catalog_send_type' => fake()->optional()->word,
            'catalog_email_text' => fake()->optional()->text,
            'send_date' => fake()->optional()->dateTime(),
            'customer_emails' => fake()->optional()->word,
            'customer_id' => Customer::factory(),
            'expiration' => fake()->optional()->dateTime(),
        ];
    }
}
