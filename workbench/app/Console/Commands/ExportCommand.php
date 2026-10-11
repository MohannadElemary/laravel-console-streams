<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;
use MohannadElemary\ConsoleStreams\Concerns\WritesDiagnosticsToStderr;

class ExportCommand extends Command
{
    use WritesDiagnosticsToStderr;

    protected $signature = 'demo:export {--fail : Throw after printing}';

    protected $description = 'Opt-in command: JSON on STDOUT, everything else on STDERR';

    public function handle(): int
    {
        $this->components->info('Exporting users');
        $this->components->task('Fetching rows', fn () => true);
        $this->components->warn('2 rows skipped');
        $this->components->twoColumnDetail('Rows', '3');
        $this->components->info('Only in verbose mode', OutputInterface::VERBOSITY_VERBOSE);
        $this->line('plain line');
        $this->info('legacy info');
        $this->comment('legacy comment');
        $this->table(['id', 'name'], [[1, 'Alice']]);
        $this->newLine();

        if ($this->option('fail')) {
            $this->components->task('Exploding', fn () => false);
            $this->components->error('export failed');

            return self::FAILURE;
        }

        $this->writeJson([['id' => 1, 'name' => 'Alice <info>tag</info>'], ['id' => 2, 'name' => 'Böb/ü']]);

        return self::SUCCESS;
    }
}
