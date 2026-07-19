<?php

namespace App\MessageHandler;

use App\Message\EtlPipelineMessage;
use App\Service\ETL\DataExtractor;
use App\Service\ETL\DataLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EtlPipelineHandler
{
    public function __construct(
        private readonly DataExtractor $extractor,
        private readonly DataLoader $loader,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(EtlPipelineMessage $message): void
    {
        try {
            // Extract data
            $customersData = $this->extractor->extractCustomersData();
            $productsData = $this->extractor->extractProductsData();
            $timeData = $this->extractor->extractTimeData();
            $salesData = $this->extractor->extractSalesData();
            $ordersData = $this->extractor->extractOrdersData();

            // Load data into the database
            $this->loader->loadCustomers($customersData);
            $this->loader->loadProducts($productsData);
            $this->loader->loadTimeData($timeData);
            $this->loader->loadSales($salesData);
            $this->loader->loadOrders($ordersData);

            // Optionally enrich from a CSV file, if one is present (see README).
            $filePath = __DIR__.'/../Csv/shopping_trends.csv';
            if (is_readable($filePath)) {
                $csvData = $this->extractor->loadFromCsv($filePath);
                $this->loader->loadCustomers($csvData['customers']);
                $this->loader->loadProducts($csvData['products']);
            }

            $this->logger->info('ETL pipeline executed successfully.');
        } catch (\Throwable $e) {
            $this->logger->error('Error executing ETL pipeline', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
