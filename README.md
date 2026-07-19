# Data Warehouse ETL Pipeline

A small **Symfony** service that builds a **star-schema data warehouse** from an
ETL pipeline. It:

1. **Extracts** data from multiple sources — generated records (Faker), a public
   exchange-rate API, and an optional CSV file.
2. **Transforms** it — normalising names, emails, and prices into a consistent
   shape.
3. **Loads** it into a **MariaDB** warehouse modelled as facts and dimensions.
4. **Automates** the whole run on a schedule (hourly) via Symfony Messenger.

- **Repo:** <https://github.com/IgliHoxha/data-warehouse-with-etl-pipeline-with-automation>
- **Stack:** Symfony 7.1 · PHP 8.2 · Doctrine ORM · Symfony Messenger + Scheduler · MariaDB
- **Runtime:** Docker (PHP CLI container + MariaDB)

---

## Quick start

Everything runs inside Docker. The `make` targets wrap the container commands.

```bash
make install-local   # copies .env, starts containers, installs deps, migrates
make etl             # run the pipeline once and populate the warehouse
```

Prefer to drive it by hand? The same steps, explicitly:

```bash
cp .env.example .env
docker compose up -d --build
docker exec -it symfony_cli composer install
docker exec -it symfony_cli php bin/console doctrine:migrations:migrate
docker exec -it symfony_cli php bin/console app:run-etl
```

This brings up two services:

| Service | Container | Purpose |
| --- | --- | --- |
| Symfony | `symfony_cli` | PHP CLI running the ETL commands. |
| MariaDB | `mariadb` | The data warehouse (exposed on `localhost:3306`). |

### Make targets

| Command | Does |
| --- | --- |
| `make up` / `down` | Start / stop the containers. |
| `make install-local` | One-shot setup: `.env`, build, install, migrate. |
| `make migrate` | Run database migrations. |
| `make etl` | Run the ETL pipeline once. |
| `make schedule` | Consume the scheduler so the pipeline runs hourly. |
| `make test` | Run the PHPUnit suite. |
| `make format` / `format-check` | Auto-format / check style (PHP-CS-Fixer). |
| `make lint` | Static analysis (PHPStan). |
| `make check` | CI-style: format check + lint + tests. |
| `make shell` | Open a shell in the Symfony container. |

Run `make help` for the full list.

---

## Environment variables (`.env`)

| Var | Purpose |
| --- | --- |
| `APP_ENV` | `dev` or `prod`. |
| `APP_SECRET` | Framework secret; generate a random hex string. |
| `DATABASE_URL` | Doctrine DSN. Defaults to the bundled MariaDB service. |
| `MESSENGER_TRANSPORT_DSN` | Scheduler transport (Doctrine-backed by default). |

Copy `.env.example` and adjust — the defaults line up with `docker-compose.yml`,
so it works unchanged for local development.

---

## The pipeline

The pipeline is three services under [`src/Service/ETL/`](src/Service/ETL/):

- **`DataExtractor`** — generates customers, products, sales, orders, and time
  records with Faker; fetches live currency rates from a public API to price
  products in EUR; and can read an optional CSV.
- **`DataTransformer`** — cleans and normalises fields (title-cases names,
  fills missing emails/prices).
- **`DataLoader`** — persists everything into the warehouse via Doctrine,
  wrapping each load in a transaction.

It can be triggered three ways:

```bash
# 1. On demand
docker exec -it symfony_cli php bin/console app:run-etl

# 2. On a schedule — every hour (see src/Schedule/EtlPipelineScheduler.php)
docker exec -it symfony_cli php bin/console messenger:consume scheduler_etl_pipeline -vv

# 3. As a message — dispatch EtlPipelineMessage onto the bus
```

> **Optional CSV source.** `app:run-etl` also enriches the warehouse from
> `src/Csv/shopping_trends.csv` if that file exists. It is not bundled; drop any
> CSV with `Customer ID`, `Age`, `Gender`, `Location`, `Item Purchased`, and
> `Category` columns there to include it, or leave it out and the step is
> skipped.

---

## Warehouse schema

A classic star schema — two fact tables (`sale`, `order`) referencing shared
dimensions, with `category`, `date`, and `week` as supporting dimensions.

```mermaid
erDiagram
    customer ||--o{ sale : places
    customer ||--o{ order : places
    product  ||--o{ sale : "sold in"
    product  ||--o{ order : "sold in"
    time     ||--o{ sale : "occurs at"
    time     ||--o{ order : "occurs at"
    category ||--o{ product : classifies
    date     ||--o{ time : "on"
    week     ||--o{ time : "in"

    customer {
        int id PK
        string name
        string email
        string location
        string gender
        int age
    }
    product {
        int id PK
        string name
        float price
        int category_id FK
    }
    sale {
        int id PK
        int customer_id FK
        int product_id FK
        int time_id FK
        float amount
        int quantity
    }
    order {
        int id PK
        int customer_id FK
        int product_id FK
        int time_id FK
        float totalAmount
        int quantity
    }
    time {
        int id PK
        int date_id FK
        int week_id FK
        string month
        int year
    }
    date {
        int id PK
        date date
        string dayName
        bool isHoliday
    }
    week {
        int id PK
        int weekNumber
        int year
    }
    category {
        int id PK
        string name
    }
```

---

## Validating the warehouse

A few SQL checks to confirm the load is complete and consistent (run against the
`symfony` database — `make shell` then `mysql`, or any client on
`localhost:3306`).

**Row counts**

```sql
SELECT COUNT(*) AS total_customers FROM customer;
SELECT COUNT(*) AS total_sales     FROM sale;
SELECT COUNT(*) AS total_orders    FROM `order`;
```

**Sales aggregated by region, product, and month**

```sql
SELECT c.location AS region, SUM(s.amount) AS total_sales
FROM sale s JOIN customer c ON s.customer_id = c.id
GROUP BY c.location ORDER BY total_sales DESC;

SELECT p.name AS product, SUM(s.quantity) AS qty_sold, SUM(s.amount) AS total_sales
FROM sale s JOIN product p ON s.product_id = p.id
GROUP BY p.name ORDER BY total_sales DESC;

SELECT t.month, SUM(s.amount) AS total_sales
FROM sale s JOIN time t ON s.time_id = t.id
GROUP BY t.month;
```

**Referential integrity — orphaned facts should return zero rows**

```sql
SELECT s.* FROM sale s
LEFT JOIN customer c ON s.customer_id = c.id
WHERE c.id IS NULL;

SELECT o.* FROM `order` o
LEFT JOIN product p ON o.product_id = p.id
WHERE p.id IS NULL;
```

**Completeness — no missing required fields**

```sql
SELECT * FROM customer WHERE name IS NULL OR email IS NULL OR location IS NULL;
SELECT * FROM product  WHERE price IS NULL;
```

---

## Tests

PHPUnit covers the ETL services (extract, transform, load).

```bash
make test
# or a single file:
make test-file F=tests/Service/ETL/DataExtractorTest.php
```

---

## Code quality

Formatting is handled by **PHP-CS-Fixer** (the `@Symfony` ruleset,
[`.php-cs-fixer.dist.php`](.php-cs-fixer.dist.php)) and static analysis by
**PHPStan** ([`phpstan.dist.neon`](phpstan.dist.neon)).

```bash
make format        # auto-format src/ and tests/
make format-check  # report style issues without writing (CI)
make lint          # PHPStan static analysis
make check         # format-check + lint + tests, in one go
```

---

## Project layout

```
src/
  Command/RunEtlPipelineCommand.php   app:run-etl — runs the pipeline on demand
  Schedule/EtlPipelineScheduler.php   hourly cron for the pipeline
  Message/EtlPipelineMessage.php      the queued ETL job
  MessageHandler/EtlPipelineHandler.php  runs the pipeline off the bus
  Service/ETL/
    DataExtractor.php    Faker + exchange-rate API + optional CSV
    DataTransformer.php  field cleaning / normalisation
    DataLoader.php       Doctrine persistence (transactional)
  Entity/                warehouse tables (facts + dimensions)
migrations/              Doctrine schema migrations
tests/Service/ETL/       unit tests for the pipeline
Dockerfile · docker-compose.yml · Makefile
```

---

## License

[MIT](LICENSE) © 2026 Igli Hoxha
