<?php
// src/Service/ETL/DataTransformer.php
namespace App\Service\ETL;

use Faker\Factory;
use Faker\Generator;

class DataTransformer
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    // Method to handle customer name transformation
    public function transformCustomerName($data): string
    {
        return isset($data['name']) ? strtoupper($data['name']) : $this->faker->name();
    }

    // Method to handle customer email transformation
    public function transformCustomerEmail($data): string
    {
        return $data['email'] ?? $this->faker->email();
    }

    // Method to handle product price transformation
    public function transformProductPrice($data): float
    {
        return $data['price'] ?? $this->faker->randomFloat(2, 10, 1000);
    }
}