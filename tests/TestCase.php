<?php

declare(strict_types=1);

namespace MohannadElemary\ConsoleStreams\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\Process\Process;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [WorkbenchServiceProvider::class];
    }

    /**
     * Run a real `testbench` subprocess (a genuine Artisan entry point) and
     * capture STDOUT and STDERR on separate OS pipes.
     *
     * @param  list<string>  $args
     * @param  array<string, string|false>  $env
     * @return array{stdout: string, stderr: string, exit: int}
     */
    protected function cli(array $args, array $env = [], ?string $input = null): array
    {
        $process = new Process(
            [PHP_BINARY, '-d', 'display_errors=stderr', dirname(__DIR__).'/vendor/bin/testbench', ...$args],
            dirname(__DIR__),
            array_merge(['NO_COLOR' => false, 'FORCE_COLOR' => false, 'COLUMNS' => '100'], $env),
            $input,
        );
        $process->run();

        return ['stdout' => $process->getOutput(), 'stderr' => $this->withoutPhpNotices($process->getErrorOutput()), 'exit' => $process->getExitCode()];
    }

    /** Old transitive deps (prefer-lowest) can emit PHP deprecations unrelated to this package. */
    private function withoutPhpNotices(string $stderr): string
    {
        return ltrim((string) preg_replace('/^(PHP )?(Deprecated|Warning|Notice):.*\R/m', '', $stderr));
    }

    /** Run a shell pipeline (e.g. `cmd | cat`) so we exercise a real pipe. */
    protected function pipeline(string $command): array
    {
        $process = Process::fromShellCommandline($command, dirname(__DIR__), ['COLUMNS' => '100']);
        $process->run();

        return ['stdout' => $process->getOutput(), 'stderr' => $this->withoutPhpNotices($process->getErrorOutput()), 'exit' => $process->getExitCode()];
    }
}
