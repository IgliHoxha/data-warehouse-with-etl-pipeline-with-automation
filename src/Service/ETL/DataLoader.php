<?php
// src/Service/ETL/DataLoader.php
namespace App\Service\ETL;

use App\Entity\Category;
use App\Entity\Date;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Time;
use App\Entity\Week;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Sale;
use App\Entity\Customer;

class DataLoader
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DataTransformer        $transformer
    )
    {
    }

    // Method to load customer data into the database
    public function loadCustomers(array $customersData): void
    {
        foreach ($customersData as $customerData) {
            $customer = new Customer();
            $customer->setName($this->transformer->transformCustomerName($customerData));
            $customer->setEmail($this->transformer->transformCustomerEmail($customerData));
            $customer->setLocation($customerData['location']);
            $customer->setGender($customerData['gender']);
            $customer->setAge($customerData['age']);

            $this->entityManager->persist($customer);
        }
        $this->entityManager->flush();
    }

    // Method to load product data into the database
    public function loadProducts(array $productsData): void
    {
        foreach ($productsData as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setPrice($this->transformer->transformProductPrice($productData));

            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $productData['category']]);
            if (!$category) {
                $category = new Category();
                $category->setName($productData['category']);
                $this->entityManager->persist($category);
                $this->entityManager->flush();
            }

            $product->setCategory($category);

            $this->entityManager->persist($product);
        }
        $this->entityManager->flush();
    }

    // Method to load time data into the database
    public function loadTimeData(array $timeData): void
    {
        foreach ($timeData as $data) {
            $dateEntity = new Date();
            $dateEntity->setDate($data['date']);
            $dateEntity->setDayName($data['day_name']);
            $dateEntity->setIsHoliday($data['is_holiday']);

            $weekEntity = $this->entityManager->getRepository(Week::class)
                ->findOneBy(['weekNumber' => $data['week_number'], 'year' => $data['year']]);

            if (!$weekEntity) {
                $weekEntity = new Week();
                $weekEntity->setWeekNumber($data['week_number']);
                $weekEntity->setYear($data['year']);
                $this->entityManager->persist($weekEntity);
            }

            $timeEntity = new Time();
            $timeEntity->setDate($dateEntity);
            $timeEntity->setWeek($weekEntity);
            $timeEntity->setMonth($data['date']->format('F'));
            $timeEntity->setYear($data['year']);

            $this->entityManager->persist($dateEntity);
            $this->entityManager->persist($timeEntity);
        }
        $this->entityManager->flush();
    }

    // Method to load sales data into the database
    public function loadSales(array $salesData): void
    {
        foreach ($salesData as $saleData) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($saleData['customer_id']);
            $product = $this->entityManager->getRepository(Product::class)->find($saleData['product_id']);
            $time = $this->entityManager->getRepository(Time::class)->find($saleData['time_id']);

            if ($customer && $product && $time) {
                $sale = new Sale();
                $sale->setCustomer($customer);
                $sale->setProduct($product);
                $sale->setTime($time);
                $sale->setAmount($saleData['amount']);
                $sale->setQuantity($saleData['quantity']);

                $this->entityManager->persist($sale);
            }
        }
        $this->entityManager->flush();
    }

    // Method to load orders data into the database
    public function loadOrders(array $ordersData): void
    {
        foreach ($ordersData as $orderData) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($orderData['customer_id']);
            $product = $this->entityManager->getRepository(Product::class)->find($orderData['product_id']);
            $time = $this->entityManager->getRepository(Time::class)->find($orderData['time_id']);

            if ($customer && $product && $time) {
                $order = new Order();
                $order->setCustomer($customer);
                $order->setProduct($product);
                $order->setTime($time);
                $order->setTotalAmount($orderData['total_amount']);
                $order->setQuantity($orderData['quantity']);

                $this->entityManager->persist($order);
            }
        }
        $this->entityManager->flush();
    }
}