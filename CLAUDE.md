# CLAUDE.md

Guidance for Claude Code (and humans) working in this repository.

## What this is

A Symfony 7.1 / PHP 8.2 service that runs an **ETL pipeline** into a
**star-schema MariaDB data warehouse**. See [README.md](README.md) for the full
overview, schema, and SQL validation queries.

## Everything runs in Docker

The app runs inside the `symfony_cli` container against a `mariadb` container.
**Do not run PHP/Composer on the host** — use the container. The `make` targets
already wrap `docker exec`, so prefer them.

```bash
make up            # build + start containers
make install-local # one-shot: .env, build, composer install, migrate
make shell         # bash inside the symfony_cli container
```

Running a command directly: `docker exec -it symfony_cli <cmd>`.

## Common commands

| Command | Does |
| --- | --- |
| `make etl` | Run the ETL pipeline once (`app:run-etl`). |
| `make schedule` | Consume the scheduler (pipeline runs hourly). |
| `make migrate` | Run Doctrine migrations. |
| `make test` | PHPUnit suite. |
| `make test-file F=...` | One test file. |
| `make format` / `format-check` | PHP-CS-Fixer (write / check). |
| `make lint` | PHPStan static analysis. |
| `make check` | format-check + lint + test — run before pushing. |

## Architecture

- **`src/Service/ETL/`** — the pipeline, three single-responsibility services:
  - `DataExtractor` — Faker-generated records + a public exchange-rate API +
    an optional CSV (`src/Csv/shopping_trends.csv`, not bundled; skipped if
    absent).
  - `DataTransformer` — field cleaning / normalisation.
  - `DataLoader` — Doctrine persistence, each load wrapped in a transaction.
- **`src/Command/RunEtlPipelineCommand.php`** — `app:run-etl`, on-demand run.
- **`src/Schedule/` + `src/Message/` + `src/MessageHandler/`** — the same
  pipeline dispatched via Symfony Messenger on an hourly cron.
- **`src/Entity/`** — the warehouse tables: facts (`Sale`, `Order`) and
  dimensions (`Customer`, `Product`, `Time`, plus `Category`, `Date`, `Week`).

`RunEtlPipelineCommand` and `EtlPipelineHandler` run the *same* steps — keep
them in sync when changing the pipeline.

## Conventions

- **Formatting:** PHP-CS-Fixer, `@Symfony` ruleset ([.php-cs-fixer.dist.php](.php-cs-fixer.dist.php)).
  Run `make format` after editing PHP.
- **Static analysis:** PHPStan level 5 ([phpstan.dist.neon](phpstan.dist.neon))
  with the Doctrine + PHPUnit extensions. Keep `make lint` at zero errors — fix
  the code, don't lower the level or add a baseline.
- **Tests:** PHPUnit under `tests/Service/ETL/`. Mock properties use
  intersection types (`Foo&MockObject`) so PHPStan sees the mock API.
- After changing an entity's schema, generate a migration
  (`doctrine:migrations:diff`) rather than editing the DB by hand.

## Before you finish

Run `make check` and make sure it is green.
