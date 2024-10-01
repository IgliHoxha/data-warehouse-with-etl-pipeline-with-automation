<?php

namespace App\MessageHandler;

use App\Message\EtlPipelineMessage;
use App\Service\ETL\DataExtractor;
use App\Service\ETL\DataLoader;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class EtlPipelineHandler
{
    public function __construct(
        private readonly DataExtractor $extractor,
        private readonly DataLoader    $loader,
        private readonly LoggerInterface        $logger,
    )
    {
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

            // Extract cvs data
            $filePath = __DIR__ . '/../Csv/shopping_trends.csv';
            $cvsData = $this->extractor->loadFromCsv($filePath);

            // Load data into the database
            $this->loader->loadCustomers($cvsData['customers']);
            $this->loader->loadProducts($cvsData['products']);
            $this->logger->info('ETL pipeline executed successfully.');
        } catch (\Throwable $e) {
            $this->logger->error('Error executing ETL pipeline', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}