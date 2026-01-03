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

    protected bool $useTransactions;

    /**
     * @param  bool  $use_transactions  Defined in behat.yml suites
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
