<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Uid\Ulid;

final class GenerateUserUlids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generate-ulids
                            {--dry-run : Run without making changes}
                            {--batch-size=1000 : Number of users to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ULIDs for existing users that do not have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('Generating ULIDs for users without ULIDs...');

        $query = User::whereNull('ulid')->orWhere('ulid', '');
        $total = $query->count();

        if ($total === 0) {
            $this->info('No users need ULIDs generated.');

            return Command::SUCCESS;
        }

        $this->info("Found {$total} users without ULIDs.");

        if ($dryRun) {
            $this->warn('DRY RUN: No changes will be made.');
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;

        $query->chunk($batchSize, function ($users) use (&$processed, $dryRun, $bar): void {
            foreach ($users as $user) {
                if (! $dryRun) {
                    $user->ulid = Ulid::generate();
                    // Set bio to null for existing users (T105.13)
                    if ($user->bio !== null) {
                        $user->bio = null;
                    }
                    $user->saveQuietly(); // Use saveQuietly to avoid triggering events
                }

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("Would have generated ULIDs for {$processed} users.");
        } else {
            $this->info("Successfully generated ULIDs for {$processed} users.");
        }

        return Command::SUCCESS;
    }
}
