<?php

// src/Command/RunEtlPipelineCommand.php

namespace App\Command;

use App\Service\ETL\DataExtractor;
use App\Service\ETL\DataLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:run-etl')]
class RunEtlPipelineCommand extends Command
{
    public function __construct(
        private readonly DataExtractor $extractor,
        private readonly DataLoader $loader,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
                $output->writeln(sprintf('Loaded supplemental data from %s.', $filePath));
            }
            $output->writeln('ETL pipeline executed successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->logger->error('Error executing ETL pipeline', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
