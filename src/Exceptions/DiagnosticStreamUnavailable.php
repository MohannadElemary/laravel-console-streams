<?php

declare(strict_types=1);

namespace MohannadElemary\ConsoleStreams\Exceptions;

use LogicException;

/**
 * Thrown in strict mode when the command runs against an output that has no
 * separate error stream (for example Artisan::call() with a BufferedOutput).
 */
final class DiagnosticStreamUnavailable extends LogicException
{
    public static function forOutput(string $outputClass): self
    {
        return new self(sprintf(
            'Cannot write diagnostics to a separate stream: [%s] is not a ConsoleOutputInterface. '
            .'Run the command through a real console (bin/artisan) or choose a different diagnosticsFallback().',
            $outputClass,
        ));
    }
}
