<?php
// src/Command/RunEtlPipelineCommand.php
namespace App\Command;

use App\Service\ETL\DataExtractor;
use App\Service\ETL\DataLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:run-etl')]
class RunEtlPipelineCommand extends Command
{

    public function __construct(
        private readonly DataExtractor $extractor,
        private readonly DataLoader $loader
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        $output->writeln('ETL pipeline executed successfully.');
        return Command::SUCCESS;
    }
}