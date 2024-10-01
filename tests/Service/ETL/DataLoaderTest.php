<?php

namespace App\Tests\Service\ETL;

use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Sale;
use App\Entity\Time;
use App\Service\ETL\DataLoader;
use App\Service\ETL\DataTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DataLoaderTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DataTransformer $transformer;
    private LoggerInterface $logger;
    private DataLoader $dataLoader;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->transformer = $this->createMock(DataTransformer::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->dataLoader = new DataLoader($this->entityManager, $this->transformer, $this->logger);
    }

    public function testLoadCustomers(): void
    {
        $customersData = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'location' => 'New York',
                'gender' => 'Male',
                'age' => 30
            ],
        ];

        // Mocking methods to ensure DataTransformer works properly
        $this->transformer->method('transformCustomerName')->willReturn('John Doe');
        $this->transformer->method('transformCustomerEmail')->willReturn('john.doe@example.com');

        // Mocking EntityManager's methods to ensure transactions are started and committed properly
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $this->dataLoader->loadCustomers($customersData);
    }

    public function testLoadCustomersWithException(): void
    {
        $customersData = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'location' => 'New York',
                'gender' => 'Male',
                'age' => 30
            ],
        ];

        // Mocking an exception during persist to simulate failure
        $this->entityManager->method('persist')->will($this->throwException(new \Exception('Test Exception')));

        // Expect that rollback and error logging are called
        $this->entityManager->expects($this->once())->method('rollback');
        $this->logger->expects($this->once())->method('error');

        $this->dataLoader->loadCustomers($customersData);
    }

    public function testLoadProducts(): void
    {
        $productsData = [
            [
                'name' => 'Product A',
                'price' => 100.12,
                'category' => 'Electronics'
            ],
        ];

        $category = new Category();
        $category->setName('Electronics');

        // Mocking methods to ensure DataTransformer works properly
        $this->transformer->method('transformProductPrice')->willReturn(100.12);

        // Mocking EntityManager's methods to ensure transactions are started and committed properly
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->exactly(2))->method('persist'); // Persist product and potentially a new category
        $this->entityManager->expects($this->exactly(2))->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        // Mock category repository to return null initially and simulate creating a new category
        $categoryRepository = $this->createMock(EntityRepository::class);
        $categoryRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->with(Category::class)->willReturn($categoryRepository);

        $this->dataLoader->loadProducts($productsData);
    }

    public function testLoadSales(): void
    {
        $salesData = [
            [
                'customer_id' => 1,
                'product_id' => 1,
                'time_id' => 1,
                'amount' => 150,
                'quantity' => 2
            ],
        ];

        $customer = new Customer();
        $product = new Product();
        $time = new Time();

        // Mock repository to return corresponding entities
        $customerRepository = $this->createMock(EntityRepository::class);
        $productRepository = $this->createMock(EntityRepository::class);
        $timeRepository = $this->createMock(EntityRepository::class);

        // Ensure the find() method returns the appropriate entity
        $customerRepository->method('find')->with(1)->willReturn($customer);
        $productRepository->method('find')->with(1)->willReturn($product);
        $timeRepository->method('find')->with(1)->willReturn($time);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Customer::class, $customerRepository],
            [Product::class, $productRepository],
            [Time::class, $timeRepository]
        ]);

        // Ensure persist, flush, and commit are called
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Sale::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $this->dataLoader->loadSales($salesData);
    }
}