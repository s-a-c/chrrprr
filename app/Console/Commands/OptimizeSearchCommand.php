<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class OptimizeSearchCommand extends Command
{
    protected $signature = 'search:optimize';

    protected $description = 'Vacuum and analyze search tables and indexes';

    public function handle(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->error('This command requires PostgreSQL.');

            return;
        }

        $this->info('Optimizing Search Schema...');

        /** @var string|mixed $appSchemaRaw */
        $appSchemaRaw = config('database.connections.pgsql.schema');
        if (! is_string($appSchemaRaw)) {
            $this->error('Database schema configuration is invalid.');

            return;
        }

        $appSchema = $appSchemaRaw;

        // Analyze specifically updates statistics for the query planner
        // crucial for the complex GIN/GIST indexes to work efficiently.
        // Optimize the main users table specifically for the vector column
        DB::statement("VACUUM (ANALYZE) \"{$appSchema}\".users");

        // Optimize the main teams table specifically for the vector column
        DB::statement("VACUUM (ANALYZE) \"{$appSchema}\".teams");

        $this->info('Search optimization complete.');
    }
}
