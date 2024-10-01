Symfony ETL Application

This is a Symfony-based ETL (Extract, Transform, Load) application designed to extract data from multiple sources, transform it, and load it into a data warehouse. The application uses Docker for containerization to ensure easy deployment and scalability.

Table of Contents

	1.	Prerequisites
	2.	Installation
	3.	Configuration
	4.	Running the ETL Pipeline
	5.	Automating the ETL Process
	6.	Running Tests
	7.	Additional Information

Prerequisites

Before setting up the project, ensure you have the following tools installed:

	•	Docker
	•	Docker Compose
	•	Git

Installation

1. Clone the Repository

Clone this repository to your local machine:

```bash
docker-compose up --build
```

This will set up the following services:

	•	Symfony: A container running the Symfony application.
	•	MariaDB: The database for storing data.

2. Install Dependencies

After the containers are up, install the PHP dependencies using Composer:

```bash
docker exec -it symfony_cli composer install
```

Configuration

1. Environment Variables

Create a .env file for environment-specific configuration, copying the contents from .env.example and updating the values as needed:

```bash 
DATABASE_URL="mysql://symfony:symfony@mariadb:3306/symfony"
```

2. Database Migration

Run the following command to migrate the database schema:

```bash
docker exec -it symfony_cli php bin/console doctrine:migrations:migrate
```

Running the ETL Pipeline

To execute the ETL pipeline manually, use the following Symfony command:

```bash
docker exec -it symfony_cli php bin/console app:run-etl
```

The ETL command performs the following operations:

	1.	Extract: Retrieves data from CSV files, APIs, and other sources.
	2.	Transform: Cleans and normalizes the data.
	3.	Load: Loads the transformed data into the data warehouse.

Automating the ETL Process

You can automate the ETL process to run on a schedule, such as every hour.

Scheduling with Symfony

To schedule the ETL process using Symfony’s scheduler, run:

```bash
docker exec -it symfony_cli php bin/console messenger:consume scheduler_etl_pipeline -vv
```

Running Tests

The application includes unit tests for the ETL processes to ensure data integrity and correctness.

1. Run All Tests

To run all tests using PHPUnit, execute:

```bash
docker exec -it symfony_cli ./vendor/bin/phpunit
```

2. Run Specific Tests

To run a specific test file:

```bash
docker exec -it symfony_cli ./vendor/bin/phpunit tests/Service/ETL/DataExtractorTest.php
```

Additional Information

Queries to validate data in the warehouse and generate basic reports:

	•	Check row counts for completeness.
	•	Aggregate data (e.g., by region, product, or month) to ensure consistency.
	•	Validate relationships between tables, ensuring there are no missing references.
	•	Identify missing or null values to maintain data integrity.
	•	Verify metrics for accuracy, such as averages or totals.

These SQL validations ensure the data in your warehouse is accurate and ready for analysis and reporting.

1. Check Row Counts

a. Total Customers

```sql
SELECT COUNT(*) AS total_customers FROM customer;
```

b. Total Products

```sql
SELECT COUNT(*) AS total_sales FROM sale;
```

c. Total Sales Records

```sql
SELECT COUNT(*) AS total_sales FROM sale;
```

d. Total Orders

```sql
SELECT COUNT(*) AS total_orders FROM `order`;
```

2. Aggregating Sales Data

a. Total Sales by Customer Location (Region)

This query will give you the total sales aggregated by customer location:

```sql
SELECT c.location AS region, SUM(s.amount) AS total_sales
FROM sale s
JOIN customer c ON s.customer_id = c.id
GROUP BY c.location
ORDER BY total_sales DESC;
```

b. Total Sales by Product

To validate product-wise sales:

```sql
SELECT p.name AS product_name, SUM(s.quantity) AS total_quantity_sold, SUM(s.amount) AS total_sales
FROM sale s
JOIN product p ON s.product_id = p.id
GROUP BY p.name
ORDER BY total_sales DESC;
```

c. Total Sales by Month

To check if sales data for all months is present:

```sql
SELECT t.month, SUM(s.amount) AS total_sales
FROM sale s
JOIN time t ON s.time_id = t.id
GROUP BY t.month
ORDER BY FIELD(t.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
```

3. Validate Foreign Key Relationships

a. Sales without a Valid Customer

To identify sales records with a missing customer:

```sql
SELECT s.*
FROM sale s
LEFT JOIN customer c ON s.customer_id = c.id
WHERE c.id IS NULL;
```

b. Orders without a Valid Product

To identify orders with a missing product:

```sql
SELECT o.*
FROM `order` o
LEFT JOIN product p ON o.product_id = p.id
WHERE p.id IS NULL;
```

4. Validate Data Completeness

a. Customers with Missing Data

This query checks if any customers have missing required fields:

```sql
SELECT *
FROM customer
WHERE name IS NULL OR email IS NULL OR location IS NULL;
```

b. Products with Missing Price

To check for products without a price:

```sql
SELECT *
FROM product
WHERE price IS NULL;
```

5. Validate Aggregated Metrics

a. Average Sales Amount per Customer

This query gives the average sales made per customer:

```sql
SELECT customer_id, AVG(amount) AS average_sales
FROM sale
GROUP BY customer_id
ORDER BY average_sales DESC;
```

b. Maximum Sales Amount per Transaction

To validate the maximum sales transaction:

```sql
SELECT MAX(amount) AS max_sales_amount FROM sale;
```

6. Compare Data Across Different Sources

a. Total Orders vs. Total Sales Comparison

Assuming there should be a certain correlation between the number of orders and sales:

```sql
SELECT 
    (SELECT COUNT(*) FROM `order`) AS total_orders,
    (SELECT COUNT(*) FROM sale) AS total_sales;
```

7. Validate Time Dimension Data

a. Count Records per Year

This query ensures that there are records for each year in the time dimension:

```sql
SELECT year, COUNT(*) AS total_records
FROM time
GROUP BY year
ORDER BY year;
```

b. Check for Missing Dates in the Time Dimension

To identify missing dates in the time table based on date_id:

```sql
SELECT d.date
FROM date d
LEFT JOIN time t ON d.id = t.date_id
WHERE t.date_id IS NULL;
```

EER Diagram:
![EER Diagram.png](EER%20Diagram.png)



