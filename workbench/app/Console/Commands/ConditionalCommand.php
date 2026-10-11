<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use MohannadElemary\ConsoleStreams\Concerns\WritesDiagnosticsToStderr;

class ConditionalCommand extends Command
{
    use WritesDiagnosticsToStderr;

    protected $signature = 'demo:conditional {--json}';

    protected function shouldWriteDiagnosticsToStderr(): bool
    {
        return (bool) $this->option('json');
    }

    public function handle(): int
    {
        $this->components->info('working');

        if ($this->option('json')) {
            $this->writeJson(['ok' => true]);
        } else {
            $this->line('{"human":"readable"}');
        }

        return self::SUCCESS;
    }
}
