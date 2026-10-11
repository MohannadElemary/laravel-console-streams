<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use MohannadElemary\ConsoleStreams\Concerns\WritesDiagnosticsToStderr;

class PromptCommand extends Command
{
    use WritesDiagnosticsToStderr;

    protected $signature = 'demo:prompt';

    public function handle(): int
    {
        $answer = $this->components->confirm('Continue?', true);
        $name = $this->ask('Name?', 'anon');
        $this->writeJson(['confirmed' => $answer, 'name' => $name]);

        return self::SUCCESS;
    }
}
