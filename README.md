# PHP Template Project

A basic starter template for PHP projects.
It provides a clean structure and example files to help you kick-start development with modern PHP tooling and best
practices.

## 💻 Technologies

* **PHP 8.5**
* **Composer** — dependency management
* **PSR-4** — autoloading standard
* **PHPUnit** — unit testing
* **Git** — version control
* **PHP-CS-Fixer** — code style fixing
* **PHPStan** — static analysis
* **Rector** — automated refactoring
* **Infection** — mutation testing
* **Mockery** — test doubles and mocks

## 📁 Project Structure

```text
php-template-project/
├── src/                 # Application source code
│   └── Example.php      # Example PHP class
├── tests/               # Test suite
│   └── ExampleTest.php  # Example test case
├── vendor/              # Composer dependencies
├── .gitignore           # Git ignore rules
├── composer.json        # Composer configuration
├── phpunit.xml          # PHPUnit configuration
├── php-cs-fixer.php     # PHP-CS-Fixer configuration
├── phpstan.neon         # PHPStan configuration
├── rector.php           # Rector configuration
└── infection.json5      # Infection configuration
```

## ▶️ Available Commands

All commands are executed via **Composer scripts**:

```bash
composer <command>
```

### 🎨 Code Style

| Command               | Description                                                   |
|-----------------------|---------------------------------------------------------------|
| `composer fix:style`  | Automatically fix code style issues using PHP-CS-Fixer        |
| `composer test:style` | Check code style without applying changes (dry-run with diff) |

### 🔁 Refactoring

| Command                | Description                                                 |
|------------------------|-------------------------------------------------------------|
| `composer refactor`    | Apply automated refactoring using Rector                    |
| `composer test:rector` | Preview refactoring changes without applying them (dry-run) |

### 🔍 Static Analysis

| Command               | Description                 |
|-----------------------|-----------------------------|
| `composer test:types` | Run PHPStan static analysis |

### 🧪 Testing

| Command                  | Description                                    |
|--------------------------|------------------------------------------------|
| `composer test:unit`     | Run PHPUnit test suite                         |
| `composer test:coverage` | Run tests with code coverage (requires Xdebug) |
| `composer test:mutation` | Run mutation testing using Infection           |

### ✅ Full Test Suite

| Command         | Description                                                    |
|-----------------|----------------------------------------------------------------|
| `composer test` | Run all checks: style, rector, types, unit tests, and mutation |
