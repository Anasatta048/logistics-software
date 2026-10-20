<?php

namespace App\Console\Commands;

use App\Services\Report\ScheduledReportRunner;
use Illuminate\Console\Command;

final class SendScheduledReports extends Command
{
    protected $signature = 'reports:send-scheduled';

    protected $description = 'Email the saved reports that are due today';

    public function handle(ScheduledReportRunner $runner): int
    {
        $this->info('Sent '.$runner->runDue().' report(s).');

        return self::SUCCESS;
    }
}
