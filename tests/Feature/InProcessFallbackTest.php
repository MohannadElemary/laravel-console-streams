<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use MohannadElemary\ConsoleStreams\Exceptions\DiagnosticStreamUnavailable;
use Symfony\Component\Console\Output\BufferedOutput;

// Artisan::call() uses a BufferedOutput: there is no separate STDERR to write to.

it('passes through unchanged by default when there is no error stream', function () {
    $out = new BufferedOutput;
    Artisan::call('demo:fallback', ['mode' => 'passthrough'], $out);

    expect($out->fetch())->toContain('diagnostic')->toContain('"ok": true');
});

it('can discard diagnostics so buffered data stays clean', function () {
    $out = new BufferedOutput;
    Artisan::call('demo:fallback', ['mode' => 'discard'], $out);

    $buffer = $out->fetch();
    expect($buffer)->not->toContain('diagnostic')
        ->and(json_decode($buffer, true))->toBe(['ok' => true]);
});

it('can fail loudly in strict mode', function () {
    Artisan::call('demo:fallback', ['mode' => 'throw'], new BufferedOutput);
})->throws(DiagnosticStreamUnavailable::class);
