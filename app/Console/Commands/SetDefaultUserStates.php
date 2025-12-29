<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserState;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use ValueError;

final class SetDefaultUserStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:set-default-states
                            {--state=active : Default state to set (pending, active, inactive)}
                            {--dry-run : Run without making changes}
                            {--batch-size=1000 : Number of users to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set default state for existing users that do not have a state set';

    /**
     * Execute the console command.
     *
     * @psalm-return 0|1
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $stateValue = $this->option('state');

        // Validate state
        try {
            $state = UserState::from($stateValue);
        } catch (ValueError) {
            $this->error("Invalid state: {$stateValue}. Must be one of: pending, active, inactive");

            return Command::FAILURE;
        }

        $this->info("Setting default state to '{$state->value}' for users without a state...");

        $query = User::query()->whereNull('state');
        $total = $query->count();

        if ($total === 0) {
            $this->info('No users need state updates.');

            return Command::SUCCESS;
        }

        $this->info("Found {$total} users without a state.");

        if ($dryRun) {
            $this->warn('DRY RUN: No changes will be made.');
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;

        $query->chunk($batchSize, static function (Collection $users) use (&$processed, $dryRun, $state, $bar): void {
            foreach ($users as $user) {
                if (! $dryRun) {
                    $user->state = $state;
                    $user->saveQuietly(); // Use saveQuietly to avoid triggering events
                }

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("Would have set state '{$state->value}' for {$processed} users.");

            return Command::SUCCESS;
        }

        $this->info("Successfully set state '{$state->value}' for {$processed} users.");

        return Command::SUCCESS;
    }
}
