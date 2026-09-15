# Context

Glossary for `scienta/doctrine-json-functions`: a library that adds JSON query functions to Doctrine ORM's DQL.

The whole library is one context: it takes a DQL function call, parses it into an AST node, and walks that node to platform-specific SQL. There is no persistence, no HTTP, and no application state of its own.

## Glossary

**Function node**
: A class under `src/Query/AST/Functions/` extending Doctrine's `FunctionNode`. It owns one DQL function end to end: how its arguments parse, and what SQL it produces. The node's `FUNCTION_NAME` constant is the DQL name a user writes (`JSON_EXTRACT`), never the class name.

**Platform**
: One database engine as Doctrine DBAL models it: MySQL, MariaDB, PostgreSQL, SQLite, SQL Server. Directories under `Functions/` are named per platform, and the same DQL concept can exist as a different node per platform. Say "platform", not "driver" or "database": a driver is the PDO layer below, and a database is one schema on a server.

**Platform base node**
: The abstract class each platform's nodes extend (`MysqlJsonFunctionNode`, `PostgresqlJsonFunctionNode`, `SqliteJsonFunctionNode`, `MssqlJsonFunctionNode`, `MariadbJsonFunctionNode`, and the shared `MysqlAndMariadbJsonFunctionNode`). Its only job is `validatePlatform()`.

**Platform validation**
: The `validatePlatform()` check that runs at SQL-generation time and throws when the connected platform isn't the one the node targets. It is a runtime guard, not a compile-time one: registering a MySQL function against a PostgreSQL connection is legal until the query runs.

**Operator node**
: A node whose SQL is an infix operator rather than a function call, via `AbstractJsonOperatorFunctionNode` and its `getOperator()`. PostgreSQL's `JSON_GET` emits `a -> b`, not `JSON_GET(a, b)`. Everything else goes through `getSqlForArgs()`, which emits `NAME(arg, arg)`.

**Argument type**
: One of the four parse strategies a node declares in `requiredArgumentTypes` and `optionalArgumentTypes`: `stringPrimary` (any DQL string expression, including a path expression or a parameter), `string` (a quoted literal only), `alphaNumeric` (a quoted literal or a number), `newValue` (a DQL assignable value). The strings themselves are the constants on `AbstractJsonFunctionNode`; call them argument types, not "arg kinds".

**Repeating optional argument**
: `allowOptionalArgumentRepeat`, which lets the final optional argument appear any number of times. `JSON_EXTRACT(doc, '$.a', '$.b', '$.c')` needs it.

**Registration**
: The consumer-side call that makes a node reachable from DQL: `$config->addCustomStringFunction(JsonExtract::FUNCTION_NAME, JsonExtract::class)`. The library ships no autoloading of functions; every function a user wants is registered by hand, so a node nobody registers is dead weight.

**DBAL compatibility shim**
: `DBALCompatibility`, the single place that hides differences between supported Doctrine majors: which platform class exists under which name (`SqlitePlatform` vs `SQLitePlatform`, `MySQLPlatform` vs `MariaDBPlatform`, `AbstractMySQLPlatform`), and how to build a "not supported" exception. New version branching goes here, never inline in a node.

**Support matrix**
: The combinations this library promises to work on, and the reason most changes are harder than they look: PHP 8.1 through 8.5, DBAL 3 and 4, ORM 2.19 and 3, across five platforms. `composer.json` holds the version ranges and `.github/workflows/ci.yml` holds which combinations CI actually runs.

**Unit suite** / **integration suite**
: The unit suite (`composer test:unit`) asserts the SQL string a node generates, with no database. An integration suite (`composer test:integration:mysql` and the four siblings) runs that SQL against a real server from `docker-compose.yml`. A function is only proven by both: the unit test pins what we emit, the integration test proves the platform accepts it.

## Terms to avoid

- **"Helper"** for a function node. It is a node; that is the Doctrine term and the one the directory structure uses.
- **"Driver"** when you mean platform. `pdo_mysql` is a driver; `MySQLPlatform` is a platform.
- **"Custom function"** on its own. Every function here is a custom function from Doctrine's point of view; the distinction that matters is which platform it targets.
