<?php
// src/Service/ETL/DataExtractor.php
namespace App\Service\ETL;

use Faker\Generator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Faker\Factory;

class DataExtractor
{
    private Generator $faker;

    public function __construct(
        private readonly HttpClientInterface $httpClient
    )
    {
        $this->faker = Factory::create();
    }

    // Method to simulate customers data using Faker
    public function extractCustomersData(int $count = 10): array
    {
        $customers = [];
        for ($i = 0; $i < $count; $i++) {
            $customers[] = [
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'location' => $this->faker->city(),
                'gender' => $this->faker->randomElement(['Male', 'Female']),
                'age' => $this->faker->numberBetween(18, 65)
            ];
        }
        return $customers;
    }

    // Method to simulate products data using Faker
    public function extractProductsData(int $count = 10): array
    {
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $products[] = [
                'name' => $this->faker->word(),
                'price' => $this->faker->randomFloat(2, 10, 1000),
            ];
        }
        return $products;
    }

    // Method to simulate time data using Faker
    public function extractTimeData(int $count = 20): array
    {
        $timeData = [];
        for ($i = 0; $i < $count; $i++) {
            $date = $this->faker->dateTimeThisYear();
            $timeData[] = [
                'date' => $date,
                'day_name' => $date->format('l'),
                'is_holiday' => $this->faker->boolean(20), // 20% chance of being a holiday
                'week_number' => $date->format('W'),
                'year' => $date->format('Y'),
            ];
        }
        return $timeData;
    }

    // Method to simulate sales data using Faker
    public function extractSalesData(int $saleCount = 20, int $customerCount = 10, int $productCount = 10, int $timeCount = 20): array
    {
        $sales = [];
        for ($i = 0; $i < $saleCount; $i++) {
            $sales[] = [
                'customer_id' => $this->faker->numberBetween(1, $customerCount),
                'product_id' => $this->faker->numberBetween(1, $productCount),
                'time_id' => $this->faker->numberBetween(1, $timeCount),
                'amount' => $this->faker->randomFloat(2, 50, 1000),
                'quantity' => $this->faker->numberBetween(1, 5),
            ];
        }
        return $sales;
    }

    // Method to simulate orders data using Faker
    public function extractOrdersData(int $orderCount = 20, int $customerCount = 10, int $productCount = 10, int $timeCount = 20): array
    {
        $orders = [];
        for ($i = 0; $i < $orderCount; $i++) {
            $orders[] = [
                'customer_id' => $this->faker->numberBetween(1, $customerCount),
                'product_id' => $this->faker->numberBetween(1, $productCount),
                'time_id' => $this->faker->numberBetween(1, $timeCount),
                'total_amount' => $this->faker->randomFloat(2, 50, 500),
                'quantity' => $this->faker->numberBetween(1, 5),
            ];
        }
        return $orders;
    }

    // Method to extract data from CSV file
    public function extractFromCsv(string $filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        return $data;
    }

    // Method to fetch data from public API (e.g., currency exchange rates)
    public function fetchFromApi(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        return $response->toArray();
    }
}