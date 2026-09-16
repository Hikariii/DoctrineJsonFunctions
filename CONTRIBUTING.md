# Contributing

Thanks for helping improve DoctrineJsonFunctions.

Domain terms are defined in [CONTEXT.md](CONTEXT.md). It is the glossary that decides what things are called, so read it before you name a class, a function node or a test.

## Table of Contents

- [Testing](#testing)
  - [Unit tests](#unit-tests)
  - [Code coverage](#code-coverage)
  - [Integration test coverage](#integration-test-coverage)
  - [Integration tests](#integration-tests)
- [Architecture](#architecture)
  - [Class Hierarchy](#class-hierarchy)
  - [Argument Types](#argument-types)
  - [Naming Convention](#naming-convention)
  - [DBAL Version Compatibility](#dbal-version-compatibility)
- [Extending the Library](#extending-the-library)
  - [Adding a new function](#adding-a-new-function)
  - [Adding a new platform](#adding-a-new-platform)


## Testing

This repository uses PHPUnit. There are two test suites:

- **Unit tests**: mock the Doctrine infrastructure, no real database needed
- **Integration tests**: run DQL queries against real MySQL, MariaDB, PostgreSQL, SQLite and SQL Server databases

### Unit tests

```bash
composer install
composer test:unit
```

Or with Docker Compose (PHP 8.4):

```bash
docker compose up -d --build --wait
docker compose exec php composer test:unit
```

### Code coverage

The Docker image includes the PCOV extension. Run the unit tests with Clover coverage output:

```bash
docker compose up -d --build --wait
docker compose run --rm php bash -c "composer install && composer test:coverage"
```

This writes `coverage.xml` to the project root. Coverage is also reported automatically on every PR and push to `master` via the [Coverage workflow](https://github.com/ScientaNL/DoctrineJsonFunctions/actions/workflows/coverage.yml).

### Integration test coverage

PCOV is available inside the container. Start the database services first, then run:

```bash
docker compose up -d --build --wait
docker compose exec php bash -c "composer install && composer test:coverage:integration"
```

This writes `coverage-integration.xml` to the project root. Integration coverage is also reported automatically on every PR alongside unit coverage.

### Integration tests

Start the database containers, then run the tests inside the PHP container:

```bash
docker compose up -d --build --wait
docker compose exec php composer test:integration
```

Run a single platform:

```bash
docker compose exec php composer test:integration:mysql
docker compose exec php composer test:integration:mariadb
docker compose exec php composer test:integration:postgres
docker compose exec php composer test:integration:sqlite
docker compose exec php composer test:integration:mssql
```

**Running locally without Docker:** copy `.env.dist` to `.env`, fill in your connection URLs, then:

```bash
export $(grep -v '^#' .env | xargs)
composer test:integration
```

SQLite always runs in-memory and needs no configuration.


## Architecture

### Class Hierarchy

The library uses a layered inheritance model to separate argument parsing (generic) from platform validation (platform-specific):

```
Doctrine\ORM\Query\AST\Functions\FunctionNode
└── AbstractJsonFunctionNode               # argument parsing, SQL generation
    ├── AbstractJsonOperatorFunctionNode   # for functions that map to SQL operators (e.g., @>, ->)
    ├── Mysql\MysqlJsonFunctionNode        # validates MySQLPlatform only
    ├── Mysql\MysqlAndMariadbJsonFunctionNode  # validates AbstractMySQLPlatform (MySQL + MariaDB)
    ├── Mariadb\MariadbJsonFunctionNode    # validates MariaDBPlatform only
    ├── Postgresql\PostgresqlJsonFunctionNode   # validates PostgreSQLPlatform
    ├── Postgresql\PostgresqlJsonOperatorFunctionNode  # PostgreSQL operator-style functions
    ├── Sqlite\SqliteJsonFunctionNode      # validates SQLitePlatform
    └── Mssql\MssqlJsonFunctionNode        # validates SQLServerPlatform
```

Each concrete function class only needs to declare:
- `FUNCTION_NAME` constant: the DQL keyword
- `$requiredArgumentTypes`: argument types that must be present
- `$optionalArgumentTypes`: argument types that may optionally be present
- `$allowOptionalArgumentRepeat`: whether optional args can repeat (variadic)

### Argument Types

| Constant | Parser Method | Accepts |
|---|---|---|
| `STRING_PRIMARY_ARG` | `StringPrimary()` | column path, parameter, subquery, string literal |
| `STRING_ARG` | literal match | single-quoted string literal only |
| `ALPHA_NUMERIC` | literal match | string, integer, or float literal |
| `VALUE_ARG` | `NewValue()` | a new value (used in insert/update functions) |

### Naming Convention

```
Scienta\DoctrineJsonFunctions\Query\AST\Functions\{Platform}\{FunctionName}
```

Examples:
- `Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql\JsonExtract`
- `Scienta\DoctrineJsonFunctions\Query\AST\Functions\Postgresql\JsonbContains`
- `Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mariadb\JsonCompact`

### DBAL Version Compatibility

`DBALCompatibility` is an internal helper that resolves class names that changed between DBAL 3 and DBAL 4:

| Platform | DBAL < 3.3 | DBAL 3.3+ / 4 |
|---|---|---|
| MariaDB | `MySQLPlatform` | `MariaDBPlatform` |
| MySQL+MariaDB shared | `MySQLPlatform` | `AbstractMySQLPlatform` |
| SQLite | `SqlitePlatform` | `SQLitePlatform` |


## Extending the Library

### Adding a new function

1. Create a class in the appropriate platform namespace extending the platform's base node class.
2. Declare `FUNCTION_NAME`, `$requiredArgumentTypes`, `$optionalArgumentTypes`, and `$allowOptionalArgumentRepeat`.
3. Override `parse()` and/or `getSqlForArgs()` only if the function has non-standard argument syntax.

**Example, a simple single-argument MySQL function:**

```php
<?php

declare(strict_types=1);

namespace Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql;

class JsonMyNewFunction extends MysqlAndMariadbJsonFunctionNode
{
    public const FUNCTION_NAME = 'JSON_MY_NEW_FUNCTION';

    protected $requiredArgumentTypes = [self::STRING_PRIMARY_ARG];
}
```

Register it:
```php
$config->addCustomStringFunction(JsonMyNewFunction::FUNCTION_NAME, JsonMyNewFunction::class);
```

Use it in DQL:
```dql
SELECT JSON_MY_NEW_FUNCTION(e.jsonColumn) FROM App\Entity\MyEntity e
```

### Adding a new platform

1. Create a new namespace folder: `src/Query/AST/Functions/{PlatformName}/`
2. Create a base node class that extends `AbstractJsonFunctionNode` and implements `validatePlatform()` to check the correct `DatabasePlatform` instance.
3. Add platform detection to `DBALCompatibility` if needed (e.g., when the class name differs between DBAL versions).
4. Implement individual function classes extending your new base.

**Example base node:**

```php
<?php

declare(strict_types=1);

namespace Scienta\DoctrineJsonFunctions\Query\AST\Functions\MyNewDb;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MyNewDbPlatform;
use Doctrine\ORM\Query\SqlWalker;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\AbstractJsonFunctionNode;

abstract class MyNewDbJsonFunctionNode extends AbstractJsonFunctionNode
{
    protected function validatePlatform(SqlWalker $sqlWalker): void
    {
        if (!$sqlWalker->getConnection()->getDatabasePlatform() instanceof MyNewDbPlatform) {
            throw new Exception("Platform not supported");
        }
    }
}
```
