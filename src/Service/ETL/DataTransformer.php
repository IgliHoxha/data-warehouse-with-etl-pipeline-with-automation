<?php
// src/Service/ETL/DataTransformer.php
namespace App\Service\ETL;

class DataTransformer
{
    // Method to clean and transform data
    public function transformTransactionalData(array $data): array
    {
        foreach ($data as $key => &$record) {
            // Handle missing values (e.g., set quantity to 1 if not provided)
            if (!isset($record['quantity'])) {
                $record['quantity'] = 1;
            }

            // Normalize fields (e.g., convert amount to float)
            $record['amount'] = floatval($record['amount']);

            // Add calculated column (e.g., total value)
            $record['total_value'] = $record['amount'] * $record['quantity'];
        }

        return $data;
    }

    // Method to transform data from CSV
    public function transformCsvData(array $csvData): array
    {
        $transformed = [];
        foreach ($csvData as $row) {
            $transformed[] = [
                'name' => $row[0],
                'email' => $row[1],
                'location' => $row[2],
            ];
        }
        return $transformed;
    }

    // Method to handle exchange rates transformation
    public function transformExchangeRates(array $data): array
    {
        // For example, convert rates to specific currency or format
        return $data;
    }
}