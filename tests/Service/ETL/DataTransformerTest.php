<?php

namespace App\Tests\Service\ETL;

use App\Service\ETL\DataTransformer;
use PHPUnit\Framework\TestCase;

class DataTransformerTest extends TestCase
{
    private DataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DataTransformer();
    }

    public function testTransformCustomerNameWithData(): void
    {
        $data = ['name' => 'JOHN DOE'];
        $result = $this->transformer->transformCustomerName($data);
        $this->assertEquals('John Doe', $result);
    }

    public function testTransformCustomerNameWithoutData(): void
    {
        $data = [];
        $result = $this->transformer->transformCustomerName($data);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testTransformCustomerEmailWithData(): void
    {
        $data = ['email' => 'john.doe@example.com'];
        $result = $this->transformer->transformCustomerEmail($data);
        $this->assertEquals('john.doe@example.com', $result);
    }

    public function testTransformCustomerEmailWithoutData(): void
    {
        $data = [];
        $result = $this->transformer->transformCustomerEmail($data);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^.+\@\S+\.\S+$/', $result);
    }

    public function testTransformProductPriceWithData(): void
    {
        $data = ['price' => 100.5];
        $result = $this->transformer->transformProductPrice($data);
        $this->assertEquals(100.5, $result);
    }

    public function testTransformProductPriceWithoutData(): void
    {
        $data = [];
        $result = $this->transformer->transformProductPrice($data);
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(10, $result);
        $this->assertLessThanOrEqual(1000, $result);
    }
}