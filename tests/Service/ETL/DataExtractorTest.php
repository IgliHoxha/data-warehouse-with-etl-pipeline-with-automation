<?php

namespace App\Tests\Service\ETL;

use App\Service\ETL\DataExtractor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DataExtractorTest extends TestCase
{
    private DataExtractor $extractor;

    protected function setUp(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Mock the HTTP client to return exchange rates data
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')
            ->willReturn([
                'rates' => [
                    'EUR' => 1.1,
                    'USD' => 1.0,
                ],
            ]);

        $httpClientMock->method('request')
            ->willReturn($responseMock);

        $this->extractor = new DataExtractor($httpClientMock, $loggerMock);
    }

    public function testExtractCustomersData(): void
    {
        $customers = $this->extractor->extractCustomersData(5);

        $this->assertCount(5, $customers);
        $this->assertArrayHasKey('name', $customers[0]);
        $this->assertArrayHasKey('email', $customers[0]);
        $this->assertArrayHasKey('location', $customers[0]);
    }

    public function testExtractProductsData(): void
    {
        $products = $this->extractor->extractProductsData(5);

        $this->assertCount(5, $products);
        $this->assertArrayHasKey('name', $products[0]);
        $this->assertArrayHasKey('price', $products[0]);
        $this->assertArrayHasKey('category', $products[0]);
    }

    public function testExtractTimeData(): void
    {
        $timeData = $this->extractor->extractTimeData(5);

        $this->assertCount(5, $timeData);
        $this->assertArrayHasKey('date', $timeData[0]);
        $this->assertArrayHasKey('day_name', $timeData[0]);
        $this->assertArrayHasKey('is_holiday', $timeData[0]);
    }

    public function testExtractSalesData(): void
    {
        $sales = $this->extractor->extractSalesData(5);

        $this->assertCount(5, $sales);
        $this->assertArrayHasKey('customer_id', $sales[0]);
        $this->assertArrayHasKey('product_id', $sales[0]);
        $this->assertArrayHasKey('amount', $sales[0]);
    }

    public function testExtractOrdersData(): void
    {
        $orders = $this->extractor->extractOrdersData(5);

        $this->assertCount(5, $orders);
        $this->assertArrayHasKey('customer_id', $orders[0]);
        $this->assertArrayHasKey('product_id', $orders[0]);
        $this->assertArrayHasKey('total_amount', $orders[0]);
    }

    public function testLoadFromCsv(): void
    {
        $csvFilePath = __DIR__ . '/test_data.csv';
        file_put_contents($csvFilePath, "Customer ID,Item Purchased,Category,Age,Gender,Location\n1,Apple,Electronics,25,Male,New York");

        $data = $this->extractor->loadFromCsv($csvFilePath);

        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertCount(1, $data['products']);
        $this->assertCount(1, $data['customers']);

        // Cleanup
        unlink($csvFilePath);
    }

    public function testFetchExchangeRates(): void
    {
        $rates = $this->extractor->fetchExchangeRates();

        $this->assertArrayHasKey('EUR', $rates);
        $this->assertEquals(1.1, $rates['EUR']);
    }
}