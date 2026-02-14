# Minimale Database

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.5-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A minimal PDO wrapper for PHP 8.5+ with event dispatching and transaction support.

## Table of Contents

- [Installation](#installation)
- [Features](#features)
- [Quick Start](#quick-start)
- [Usage](#usage)
  - [Supported Drivers](#supported-drivers)
  - [Basic Queries](#basic-queries)
  - [Transactions](#transactions)
  - [Event Dispatching](#event-dispatching)
  - [Data Transformation](#data-transformation)
  - [Driver Registry](#driver-registry)
  - [Custom Drivers](#custom-drivers)
- [Requirements](#requirements)
- [Testing](#testing)
- [License](#license)

## Installation

```bash
composer require minimale/database
```

## Features

- **Simple API** — Clean interface for database operations
- **Event Dispatching** — PSR-14 compatible event system
- **Transaction Support** — Built-in transaction management
- **Multiple Drivers** — SQLite and Firebird support
- **Type Safety** — Strict types throughout
- **Query Normalization** — Automatic parameter binding and query normalization
- **Data Transformation** — Extensible data transformation layer

## Quick Start

```php
use Minimale\Database\DatabaseManager;
use Minimale\Database\Driver\SQLiteDriver;

// Create and connect driver
$driver = new SQLiteDriver();
$driver->connect('sqlite::memory:');

// Initialize manager
$db = new DatabaseManager($driver);

// Create table
$db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');

// Insert data
$db->execute('INSERT INTO users (name) VALUES (?)', ['John']);

// Query data
$result = $db->execute('SELECT * FROM users WHERE id = ?', [1]);
$user = $result->fetch();

// Transactions
$db->beginTransaction();
try {
    $db->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}
```

## Usage

### Supported Drivers

#### SQLite

```php
use Minimale\Database\Driver\SQLiteDriver;

$driver = new SQLiteDriver();
$driver->connect('sqlite::memory:');

// Or with file
$driver = new SQLiteDriver();
$driver->connect('sqlite:/path/to/database.db');
```

#### Firebird

```php
use Minimale\Database\Driver\FirebirdDriver;

$driver = new FirebirdDriver();
$driver->connect(
    'firebird:dbname=localhost:/path/to/database.fdb',
    'SYSDBA',
    'masterkey'
);
```

### Basic Queries

```php
// Fetch single row
$result = $db->execute('SELECT * FROM users WHERE id = ?', [1]);
$user = $result->fetch();

// Fetch single value
$count = $db->execute('SELECT COUNT(*) FROM users')->fetchValue();

// Fetch all rows
$users = $db->execute('SELECT * FROM users')->fetchAll();

// Get affected row count
$result = $db->execute('DELETE FROM users WHERE active = ?', [0]);
$deleted = $result->rowCount();
```

### Transactions

```php
$db->beginTransaction();

try {
    $db->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    $db->execute('INSERT INTO logs (action) VALUES (?)', ['user_created']);
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Event Dispatching

```php
use Minimale\Database\Driver\SQLiteDriver;

$driver = new SQLiteDriver(
    eventDispatcher: $eventDispatcher // Your PSR-14 event dispatcher
);
```

The library dispatches the following events:

- **ConnectionEstablishedEvent** — Fired when a database connection is successfully established
- **ConnectionClosedEvent** — Fired when a database connection is closed
- **QueryExecutedEvent** — Fired after a query is executed (includes query, parameters, and execution time)
- **TransactionBeganEvent** — Fired when a transaction starts
- **TransactionCommittedEvent** — Fired when a transaction is committed
- **TransactionRolledBackEvent** — Fired when a transaction is rolled back

### Data Transformation

The library includes a data transformation layer that automatically handles encoding/decoding of data between PHP and the database. Each driver can have its own transformer:

- **PassthroughDataTransformer** (SQLite default) — No transformation, passes data as-is
- **FirebirdDataTransformer** (Firebird default) — Handles Firebird-specific data types and conversions

You can also implement custom transformers by implementing the `DataTransformerInterface`:

```php
use Minimale\Database\Driver\DataTransformer\DataTransformerInterface;

class CustomTransformer implements DataTransformerInterface
{
    public function encode(mixed $value): mixed
    {
        // Transform PHP value before storing in database
        return $value;
    }

    public function decode(mixed $value): mixed
    {
        // Transform database value after fetching
        return $value;
    }
}

// Use custom transformer
$driver = new SQLiteDriver(
    eventDispatcher: $eventDispatcher,
    dataTransformer: new CustomTransformer()
);
```

### Driver Registry

The `DriverRegistry` allows you to manage multiple database connections with named aliases, making it easy to work with
multiple databases in your application.

```php
use Minimale\Database\DriverRegistry;
use Minimale\Database\Driver\SQLiteDriver;

$registry = new DriverRegistry();

// Register multiple drivers
$sqliteDriver = new SQLiteDriver();
$sqliteDriver->connect('sqlite:/path/to/db.sqlite');
$registry->add('sqlite', $sqliteDriver);

// Retrieve and use registered drivers
$driver = $registry->get('sqlite');
$db = new DatabaseManager($driver);

// Check if driver exists
if ($registry->has('sqlite')) {
    // Use SQLite connection
}

// List all registered aliases
$aliases = $registry->all(); // ['sqlite']

// Remove a driver
$registry->remove('sqlite');
```

**Available methods:**

- **`add(string $alias, DriverInterface $driver): void`** — Register a driver with an alias
- **`get(string $alias): DriverInterface`** — Retrieve a registered driver by alias
- **`has(string $alias): bool`** — Check if a driver is registered
- **`remove(string $alias): void`** — Remove a driver from the registry
- **`all(): array`** — Get all registered driver aliases

### Custom Drivers

You can create custom drivers by implementing the `DriverInterface`:

```php
use Minimale\Database\Driver\DriverInterface;
use Minimale\Database\Result;

class CustomDriver implements DriverInterface
{
    public function connect(string $dsn, ?string $username = null, ?string $password = null): void
    {
        // Implement connection logic
    }

    public function execute(string $query, array $parameters = []): Result
    {
        // Implement query execution
    }

    public function disconnect(): void
    {
        // Implement disconnection logic
    }

    public function beginTransaction(): void
    {
        // Implement transaction start
    }

    public function commit(): void
    {
        // Implement transaction commit
    }

    public function rollback(): void
    {
        // Implement transaction rollback
    }
}
```

## Requirements

- PHP 8.5 or higher
- PDO extension
- PSR Event Dispatcher (psr/event-dispatcher)

## Testing

```bash
# Run all tests
composer test

# Unit tests only
composer test:unit

# Code coverage
composer test:coverage

# Mutation testing
composer test:mutation

# Static analysis
composer test:types
```

## License

MIT
