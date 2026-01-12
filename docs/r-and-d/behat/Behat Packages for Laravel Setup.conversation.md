# Behat Packages for Laravel Setup

## best set of behat packages for laravel

When setting up Behat for a modern Laravel project (Laravel 10, 11, or 12), the ecosystem has shifted toward a few specific, well-maintained forks and extensions. While the original Laracasts extension was the pioneer, many developers now prefer community-maintained versions that support PHP 8.x+ and the latest Laravel versions.

Here is the best set of packages to use for a robust BDD (Behavior Driven Development) setup.

---

### 1. Core Integration Package

    You need an extension that "boots" Laravel within Behat so you can access your models, facades, and database.

    * **Primary Choice:** `cevinio/behat-laravel-extension`
    This is currently the most up-to-date fork of the original Laracasts extension. It supports Laravel 10/11/12 and PHP 8.2+. It allows you to use the `LaravelAwareContext` trait to access `$this->app` directly in your tests.
    * **Alternative:** `friends-of-behat/laravel-extension`
    Part of the "Friends of Behat" organization, which is known for high-quality maintenance of Behat tools. It is very stable but occasionally trails slightly behind the latest Laravel releases.

---

### 2. Browser & UI Testing (Mink)

Behat on its own doesn't "see" a browser. You need **Mink** to interact with your web pages.

* **`behat/mink-extension`**: The bridge between Behat and Mink.
* **`behat/mink-browserkit-driver`**: This is essential for **fast** tests. It simulates a browser within the PHP process (no real Chrome/Firefox needed). It’s perfect for testing forms and links that don't require JavaScript.
* **`behat/mink-selenium2-driver`**: Use this only when you need to test JavaScript-heavy features (like Vue/React components). It requires a Selenium or ChromeDrive server.

---

### 3. Recommended Composer Command

    To get a fully functional "best-in-class" setup, run:

```bash
composer require --dev \
    behat/behat \
    cevinio/behat-laravel-extension \
    behat/mink-extension \
    behat/mink-browserkit-driver

```

---

### 4. Ideal `behat.yml` Configuration

Once installed, create a `behat.yml` in your root. This configuration tells Behat to use Laravel as the default driver for your sessions.

```yaml
default:
  extensions:
    Cevinio\Behat:
      # Use a dedicated .env.testing file for your DB
      env_path: .env.testing
    Behat\MinkExtension:
      # 'laravel' session uses the BrowserKit driver inside your app
      default_session: laravel
      laravel: ~
      # Base URL for when you use Selenium/real browsers
      base_url: http://localhost:8000
  suites:
    default:
      contexts:
        - FeatureContext
        - Behat\MinkExtension\Context\MinkContext

```

---

### 5. Pro-Tip: Database Management

    Because Behat runs outside of Laravel’s internal test runner (unlike PHPUnit or Pest), it doesn't automatically wrap tests in transactions. To keep your database clean:

    1. **Use a dedicated DB**: Always use `.env.behat` or `.env.testing`.
    2. **Hooks**: In your `FeatureContext.php`, use the `@BeforeScenario` hook to run `Artisan::call('migrate:fresh')` or use a trait to handle transactions if you are using the BrowserKit driver.

---

## Would you like me to provide a template for a `FeatureContext` file that includes Laravel-specific authentication steps (e.g., "Given I am logged in as a user")?

_**Yes.**_

To get you started, here is a robust `FeatureContext.php` template. This implementation uses the `LaravelAwareContext` trait, which gives you access to the Laravel Service Container, allowing you to interact with your models and the `Auth` facade directly.

### The `FeatureContext` Template

Add this file to your `features/bootstrap/` directory. It includes common Laravel-specific steps like user authentication and database cleanup.

```php
<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Cevinio\Behat\Context\LaravelAwareContext;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class FeatureContext extends RawMinkContext implements Context, LaravelAwareContext
{
    /**
     * @BeforeScenario
     */
    public function refreshDatabase()
    {
        // Migrates the database before every scenario to ensure a clean state
        Artisan::call('migrate:fresh');
    }

    /**
     * @Given I am logged in as a user
     */
    public function iAmLoggedInAsAUser()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Using the Laravel extension to log the user in
        Auth::login($user);

        // If using BrowserKit/Mink, you may need to visit a page to persist the session
        $this->visitPath('/dashboard');
    }

    /**
     * @Given a user exists with email :email
     */
    public function aUserExistsWithEmail($email)
    {
        User::factory()->create(['email' => $email]);
    }

    /**
     * @Then the database should have a user with email :email
     */
    public function theDatabaseShouldHaveAUserWithEmail($email)
    {
        $count = User::where('email', $email)->count();

        if ($count === 0) {
            throw new \Exception("User with email {$email} was not found in the database.");
        }
    }
}

```

---

### Implementation Tips for Laravel

    * **Traits over Bloat:** As your test suite grows, avoid putting everything in `FeatureContext`. Create specific Context files (e.g., `AuthenticationContext`, `ApiContext`) and list them under the `contexts` key in your `behat.yml`.
    * **The `.env.testing` File:** Ensure your `behat.yml` points to a testing environment. Using an in-memory SQLite database (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) is the fastest way to run these tests, though it won't work if you are using a real browser via Selenium (as the browser and the test won't share the memory).
    * **Fast Authentication:** Instead of filling out the login form in every scenario (which is slow), use the `Auth::login($user)` method shown above for "Given I am logged in" steps, and only test the actual login form in one dedicated feature file.

---

## Would you like me to show you how to configure the `behat.yml` to split these contexts into multiple files for better organization?

_**review attached and make recommendations**_

- behat.yml
- features/bootstrap
├── ApiContext.php
├── BrowserContext.php
├── CqrsContext.php
├── FeatureContext.php
├── LaravelContext.php
└── TenantContext.php

After reviewing your provided configuration files and Behat contexts, you have a very sophisticated setup that leverages several advanced patterns (Multi-tenancy, CQRS, and Result monads).

However, there are a few critical architectural conflicts and optimization opportunities—specifically regarding how Laravel is bootstrapped and how your contexts interact.

---

### 1. Eliminate "Double Bootstrapping"

    You have a significant conflict between your `behat.yml` and `LaravelContext.php`.

    * **The Issue:** Your `behat.yml` uses `cevinio/behat-laravel-extension`, which automatically boots Laravel and provides it to any context implementing `LaravelAwareContext`. However, your `LaravelContext.php` manually boots the app in a `@BeforeScenario` hook.
    * **The Risk:** This can cause "Service Container pollution," where singletons or database connections are initialized twice, leading to "Database not found" errors or authentication state mismatches between contexts.

    **Recommendation:**
    Update `LaravelContext.php` to use the extension's native capabilities:

```php
// Change this:
class LaravelContext implements Context { ... }

// To this:
use Cevinio\Behat\Context\LaravelAwareContext;

class LaravelContext implements Context, LaravelAwareContext
{
    // Remove bootstrapLaravel() entirely.
    // The extension will inject the app into $this->app automatically.
}

```

---

### 2. Database Performance (Transaction vs. Refresh)

Your `LaravelContext.php` runs `migrate:fresh` before **every** scenario. While safe, this is extremely slow as your migration count grows.

**Recommendation:**
Since you are using PostgreSQL (`pgsql`), use **Database Transactions** for your `@unit` and `@api` suites.

1. Migrate the database once before the entire test suite runs.
2. Wrap each scenario in a transaction and roll it back afterward.
3. **Note:** Keep `migrate:fresh` for the `@browser` suite, as Selenium/Playwright runs in a separate process and cannot see the transaction of the test process.

---

### 3. Context Communication

    In `FeatureContext.php`, you are using `gatherContexts` to manually grab other contexts. While functional, Behat provides a cleaner way to handle this using **Context Injection** if you move to Behat 3.x+ native constructors.

    **Recommendation:**
    Instead of `gatherContexts`, use the constructor to define dependencies. This makes the code more testable and easier for IDEs to track:

```php
public function __construct(TenantContext $tenantContext, ApiContext $apiContext)
{
    $this->tenantContext = $tenantContext;
    $this->apiContext = $apiContext;
}

```

---

### 4. Multi-Tenancy Logic Gap

In `TenantContext.php`, you have a step `iAmInTenantContext` that calls `tenancy()->initialize($this->currentTenant)`.

**Recommendation:**
Ensure that your `behat.yml` for the `@browser` suite correctly passes the `APP_URL`. If your multi-tenancy relies on subdomains (e.g., `tenant1.chrrprr.test`), the `base_url` in `behat.yml` needs to be dynamically updated or your `TenantContext` needs to call `$this->getSession()->visit($tenantUrl)`. Currently, your browser tests stay on `chrrprr.test`, which might bypass your tenancy middleware if it's domain-based.

---

### 5. Security & Environment Alignment

    Your `.env.testing` specifies `DB_SCHEMA=chrrprr-testing`.

    **Recommendation:**
    Verify that your Postgres user (`root`) has explicit permissions to create and drop that specific schema. Often, developers forget that `migrate:fresh` in Postgres requires schema-level ownership.

---

### Summary Table of Recommendations

| Component | Current State | Recommended Change |
| --- | --- | --- |
| **Bootstrapping** | Manual in `LaravelContext` | Rely on `LaravelAwareContext` trait. |
| **DB Cleanup** | `migrate:fresh` every time | Use Transactions for `@unit` / `@api`. |
| **Auth Logic** | Form-based in `iLoginWith...` | Use `Auth::login($user)` for "Given" steps to save time. |
| **Driver** | Playwright/Chrome | Ensure `api_url` 9222 is active in your CI/CD runner. |

---

## Would you like me to rewrite the `behat.yml` to support these separated database strategies for Unit vs. Browser suites?

    _**yes, also provide updated context files**_

    To implement a high-performance Behat architecture, we will shift to **Database Transactions** for non-browser tests and clean up the **Context Injection**. This ensures that your `@api` and `@unit` tests run in milliseconds while keeping your `@browser` tests isolated.

### 1. Updated `behat.yml`

    This configuration separates the database strategy. The `api` and `unit` suites will benefit from faster execution, while `browser` retains the full refresh required for external browser processes.

```yaml
default:
  suites:
    browser:
      paths: ["%paths.base%/features"]
      filters: { tags: "@browser" }
      contexts:
        - FeatureContext
        - BrowserContext
        - LaravelContext
        - TenantContext
        - CqrsContext
    api:
      paths: ["%paths.base%/features"]
      filters: { tags: "@api" }
      contexts:
        - FeatureContext
        - ApiContext
        - LaravelContext
        - TenantContext
        - CqrsContext
    unit:
      paths: ["%paths.base%/features"]
      filters: { tags: "@unit" }
      contexts:
        - FeatureContext
        - LaravelContext:
            use_transactions: true # Custom parameter for speed
        - TenantContext
        - CqrsContext

  extensions:
    Cevinio\Behat:
      bootstrap_path: 'bootstrap/app.php' #
      [cite_start]env_path: '.env.testing' # Points to your pgsql config [cite: 1]
    Behat\MinkExtension:
      base_url: 'http://chrrprr.test' #
      default_session: 'default'
      sessions:
        default:
          browserkit_http: ~
        chrome:
          api_url: 'http://127.0.0.1:9222' #

```

---

### 2. Updated `LaravelContext.php`

We now implement `LaravelAwareContext`. This allows the extension to handle the heavy lifting of bootstrapping.

```php
<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Cevinio\Behat\Context\LaravelAwareContext;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class LaravelContext implements Context, LaravelAwareContext
{
    protected Application $app;
    protected bool $useTransactions = false;

    public function __construct(bool $use_transactions = false)
    {
        $this->useTransactions = $use_transactions; //
    }

    public function setApp(Application $app): void
    {
        $this->app = $app; // Injected by Cevinio extension
    }

    /** @BeforeScenario */
    public function prepareDatabase(): void
    {
        if ($this->useTransactions) {
            DB::beginTransaction(); //
        } else {
            Artisan::call('migrate:fresh', ['--env' => 'testing']); //
        }
    }

    /** @AfterScenario */
    public function cleanupDatabase(): void
    {
        if ($this->useTransactions) {
            DB::rollBack(); //
        }
    }
}

```

---

### 3. Updated `FeatureContext.php`

    We replace the manual `gatherContexts` with **Constructor Injection**. This is the standard way to share state between contexts in Behat.

```php
<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Cevinio\Behat\Context\LaravelAwareContext;
use Illuminate\Foundation\Application;

class FeatureContext extends MinkContext implements Context, LaravelAwareContext
{
    protected Application $app;
    protected TenantContext $tenantContext;
    protected CqrsContext $cqrsContext;

    // Behat automatically injects these contexts if they are in the same suite
    public function __construct(TenantContext $tenantContext, CqrsContext $cqrsContext)
    {
        $this->tenantContext = $tenantContext;
        $this->cqrsContext = $cqrsContext;
    }

    public function setApp(Application $app): void
    {
        $this->app = $app;
    }

    /**
     * @Given I am logged in as :email
     */
    public function iAmLoggedInAs(string $email): void
    {
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) throw new Exception("User not found");

        // Use the injected app to handle login without form submission
        $this->app['auth']->login($user);
        $this->tenantContext->setCurrentUser($user); //
    }
}

```

---

### 4. Updated `TenantContext.php`

Ensuring the multi-tenancy logic respects the Laravel application state.

```php
<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use App\Models\Enterprise;

class TenantContext implements Context
{
    protected ?Enterprise $currentTenant = null;

    /**
     * @Given I am in tenant context :tenantName
     */
    public function iAmInTenantContext(string $tenantName): void
    {
        $this->currentTenant = Enterprise::where('name->en', $tenantName)
            ->orWhere('name', 'like', "%{$tenantName}%")
            ->first();

        if (!$this->currentTenant) throw new Exception("Tenant not found");

        // Trigger your tenancy helper
        tenancy()->initialize($this->currentTenant);
    }
}

```

## Would you like me to create a `Makefile` or a custom `composer script` to automate the startup of the Chrome/Playwright debug server for your browser tests?

_**yes: composer script**_

To automate your workflow, I have updated the `composer.json` scripts to handle the environment setup, database preparation, and the Playwright/Chrome debugging server required for your `@browser` suite.

### 1. Updated `composer.json` Scripts

Add these to your `scripts` section. These commands ensure that your `.env.testing` is used and that the browser server is running before tests start.

```json
"scripts": {
    "test:setup": [
        "@php -r \"file_exists('.env.testing') || copy('.env.example', '.env.testing');\"",
        "php artisan key:generate --env=testing"
    ],
    "test:browser-server": "open -a \"Google Chrome\" --args --remote-debugging-port=9222 --headless",
    "behat:unit": "behat --suite=unit --tags='@unit'",
    "behat:api": "behat --suite=api --tags='@api'",
    "behat:browser": "behat --suite=browser --tags='@browser'",
    "behat:all": [
        "@behat:unit",
        "@behat:api",
        "@behat:browser"
    ]
}

```

---

### 2. Final Context File Updates

#### `LaravelContext.php` (The Optimizer)

    This context now acts as the gatekeeper for database state, using the `use_transactions` parameter from your `behat.yml`.

```php
<?php
declare(strict_types=1);

namespace Tests\Behat;

use Behat\Behat\Context\Context;
use Cevinio\Behat\Context\LaravelAwareContext;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class LaravelContext implements Context, LaravelAwareContext
{
    protected Application $app;
    protected bool $useTransactions;

    /**
     * @param bool $use_transactions Defined in behat.yml suites
     */
    public function __construct(bool $use_transactions = false)
    {
        $this->useTransactions = $use_transactions;
    }

    public function setApp(Application $app): void
    {
        $this->app = $app;
    }

    /** @BeforeScenario */
    public function setupDatabase(): void
    {
        if ($this->useTransactions) {
            // Fast: Just wrap the scenario in a transaction
            DB::beginTransaction();
        } else {
            // Slow but Necessary: Refresh for Browser/Playwright tests
            Artisan::call('migrate:fresh', ['--env' => 'testing']);
        }
    }

    /** @AfterScenario */
    public function rollbackDatabase(): void
    {
        if ($this->useTransactions && DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
}

```

#### `FeatureContext.php` (The Orchestrator)

    This uses **Constructor Injection** to access your specialized contexts, ensuring `TenantContext` always knows which user is active.

```php
<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Cevinio\Behat\Context\LaravelAwareContext;
use Illuminate\Foundation\Application;

class FeatureContext extends MinkContext implements Context, LaravelAwareContext
{
    protected Application $app;
    protected TenantContext $tenantContext;

    /**
     * Behat injects these automatically via the suite configuration
     */
    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function setApp(Application $app): void
    {
        $this->app = $app;
    }

    /**
     * @Given I am authenticated as :email
     */
    public function iAmAuthenticatedAs(string $email): void
    {
        $user = \App\Models\User::where('email', $email)->first()
            ?? \App\Models\User::factory()->create(['email' => $email]);

        // Access Laravel directly through the injected app
        $this->app['auth']->login($user);

        // Share state with TenantContext
        $this->tenantContext->setCurrentUser($user);
    }
}

```

### 3. Usage Guide

    1.
    **First-time setup:** Run `composer test:setup` to prepare your `.env.testing`.


    2. **Running UI Tests:** * Open your browser server: `composer test:browser-server`.
    * Run tests: `composer behat:browser`.


    3. **CI/CD Pipeline:** Use `composer behat:all` to run the fast transactions first and the browser tests last.

---
