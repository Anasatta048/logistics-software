<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoService;
use Illuminate\Console\Command;

/** Every few minutes: delete demo sandboxes whose hour is over, with their data and files. */
final class PurgeDemos extends Command
{
    protected $signature = 'demo:purge {--all : Remove every demo sandbox, even ones still running}';

    protected $description = 'Delete expired demo workspaces and their data';

    public function handle(DemoService $demo): int
    {
        $this->info('Removed '.$demo->purge((bool) $this->option('all')).' demo workspace(s).');

        return self::SUCCESS;
    }
}
