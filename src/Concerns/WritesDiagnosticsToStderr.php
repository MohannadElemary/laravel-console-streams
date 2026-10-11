<?php

declare(strict_types=1);

namespace MohannadElemary\ConsoleStreams\Concerns;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Laravel\Prompts\Prompt;
use MohannadElemary\ConsoleStreams\Exceptions\DiagnosticStreamUnavailable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Opt-in trait for Artisan commands: keeps machine data on STDOUT and routes
 * everything a command prints through $this->components, $this->line(),
 * $this->info(), $this->table() ... to STDERR.
 *
 * Only commands that `use` this trait are affected. Nothing is changed globally.
 *
 * How it works: Symfony calls initialize() after Laravel has built
 * $this->output / $this->components and after the input is bound. The trait
 * keeps the real STDOUT OutputStyle for data and swaps the two public Laravel
 * properties for ones backed by ConsoleOutput::getErrorOutput(). No framework
 * class is overridden or extended.
 *
 * @mixin Command
 */
trait WritesDiagnosticsToStderr
{
    /** The untouched STDOUT-backed output, reserved for machine-readable data. */
    private ?OutputStyle $dataOutput = null;

    /**
     * Opt-in switch evaluated at start-up. Override to make the redirect
     * conditional, e.g. `return (bool) $this->option('json');`.
     */
    protected function shouldWriteDiagnosticsToStderr(): bool
    {
        return true;
    }

    /**
     * What to do when the output has no separate error stream (Artisan::call(),
     * BufferedOutput, StreamOutput ...):
     *
     *  - 'passthrough' (default): leave the command untouched; diagnostics and data share one stream.
     *  - 'discard':               drop diagnostics so data stays clean.
     *  - 'throw':                 throw DiagnosticStreamUnavailable.
     *
     * @return 'passthrough'|'discard'|'throw'
     */
    protected function diagnosticsFallback(): string
    {
        return 'passthrough';
    }

    /**
     * Symfony lifecycle hook. If your command defines initialize() itself,
     * call `$this->initializeDiagnosticStreams($input, $output)` from it instead.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->initializeDiagnosticStreams($input, $output);
    }

    protected function initializeDiagnosticStreams(InputInterface $input, OutputInterface $output): void
    {
        $this->dataOutput = $this->output;

        if (! $this->shouldWriteDiagnosticsToStderr()) {
            return;
        }

        $stderr = $this->resolveErrorOutput($this->output);

        if ($stderr === null) {
            $stderr = match ($this->diagnosticsFallback()) {
                'discard' => new NullOutput,
                'throw' => throw DiagnosticStreamUnavailable::forOutput($this->output::class),
                default => null,
            };

            if ($stderr === null) {
                return; // passthrough: behave exactly like a normal command
            }
        }

        $style = new OutputStyle($input, $stderr);

        $this->output = $style;
        $this->components = new Factory($style);

        // Keep question/prompt rendering off STDOUT as well (also what restorePrompts() re-applies).
        if (class_exists(Prompt::class)) {
            Prompt::setOutput($style);
        }
    }

    /**
     * The STDOUT-backed output for machine-readable data. Never redirected.
     */
    protected function stdout(): OutputStyle
    {
        return $this->dataOutput ?? $this->output;
    }

    /**
     * Write raw data to STDOUT. Not parsed for <tags>, not decorated, and
     * printed even with --quiet (data is the command's result, not chatter).
     */
    protected function writeStdout(string $data, bool $newline = true): void
    {
        $this->stdout()->write($data, $newline, OutputInterface::OUTPUT_RAW | OutputInterface::VERBOSITY_QUIET);
    }

    /**
     * Encode a value as JSON and write it to STDOUT.
     */
    protected function writeJson(mixed $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): void
    {
        $this->writeStdout(json_encode($data, $flags | JSON_THROW_ON_ERROR));
    }

    private function resolveErrorOutput(OutputInterface $output): ?OutputInterface
    {
        while ($output instanceof OutputStyle) {
            $output = $output->getOutput();
        }

        return $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : null;
    }
}
