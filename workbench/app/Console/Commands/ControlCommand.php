<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;

/** Same output as demo:export, but without the trait: must stay entirely on STDOUT. */
class ControlCommand extends Command
{
    protected $signature = 'demo:control';

    public function handle(): int
    {
        $this->components->info('control info');
        $this->components->task('Control task', fn () => true);
        $this->line('control line');
        $this->output->writeln('{"control":true}');

        return self::SUCCESS;
    }
}
