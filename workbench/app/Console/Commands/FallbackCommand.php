<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use MohannadElemary\ConsoleStreams\Concerns\WritesDiagnosticsToStderr;

class FallbackCommand extends Command
{
    use WritesDiagnosticsToStderr;

    protected $signature = 'demo:fallback {mode}';

    protected function diagnosticsFallback(): string
    {
        return $this->argument('mode');
    }

    public function handle(): int
    {
        $this->components->info('diagnostic');
        $this->writeJson(['ok' => true]);

        return self::SUCCESS;
    }
}
