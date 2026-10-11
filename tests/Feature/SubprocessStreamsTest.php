<?php

declare(strict_types=1);

const ESC = "\e[";

it('keeps STDOUT as pure JSON and sends every component to STDERR', function () {
    $r = $this->cli(['demo:export']);

    expect($r['exit'])->toBe(0);

    $json = json_decode($r['stdout'], true, flags: JSON_THROW_ON_ERROR);
    expect($json)->toHaveCount(2)
        ->and($json[0]['name'])->toBe('Alice <info>tag</info>') // raw: tags in data are not parsed
        ->and($json[1]['name'])->toBe('Böb/ü');

    expect($r['stderr'])
        ->toContain('INFO')->toContain('Exporting users')
        ->toContain('Fetching rows')->toContain('DONE')   // components->task()
        ->toContain('WARN')->toContain('2 rows skipped')
        ->toContain('plain line')->toContain('legacy info')->toContain('legacy comment')
        ->toContain('Alice')->toContain('+----') // table
        ->not->toContain('"id"');
});

it('does not alter commands that do not use the trait (no global side effects)', function () {
    $r = $this->cli(['demo:control']);

    expect($r['stderr'])->toBe('')
        ->and($r['stdout'])->toContain('control info')->toContain('Control task')->toContain('DONE')
        ->toContain('control line')->toContain('{"control":true}');
});

it('still prints data with --quiet while silencing diagnostics', function () {
    $r = $this->cli(['demo:export', '--quiet']);

    expect(json_decode($r['stdout'], true))->toHaveCount(2)
        ->and($r['stderr'])->toBe('');
});

it('honours verbosity on the error stream', function () {
    $normal = $this->cli(['demo:export']);
    $verbose = $this->cli(['demo:export', '-v']);

    expect($normal['stderr'])->not->toContain('Only in verbose mode')
        ->and($verbose['stderr'])->toContain('Only in verbose mode')
        ->and(json_decode($verbose['stdout'], true))->toHaveCount(2);
});

it('emits no ANSI escapes with --no-ansi', function () {
    $r = $this->cli(['demo:export', '--no-ansi']);

    expect($r['stderr'])->not->toContain("\e")->toContain('DONE')
        ->and($r['stdout'])->not->toContain("\e");
});

it('keeps ANSI on STDERR only when forced with --ansi, never in JSON', function () {
    $r = $this->cli(['demo:export', '--ansi']);

    expect($r['stderr'])->toContain(ESC)
        ->and($r['stdout'])->not->toContain("\e")
        ->and(json_decode($r['stdout'], true))->toHaveCount(2);
});

it('does not decorate piped streams by default', function () {
    $r = $this->cli(['demo:export']);

    expect($r['stderr'])->not->toContain("\e")->and($r['stdout'])->not->toContain("\e");
});

it('preserves a non-zero exit code and keeps failures on STDERR', function () {
    $r = $this->cli(['demo:export', '--fail']);

    expect($r['exit'])->toBe(1)
        ->and($r['stdout'])->toBe('')
        ->and($r['stderr'])->toContain('ERROR')->toContain('export failed');
});

it('works in a real shell pipe: stderr passes through, stdout is piped', function () {
    $php = escapeshellarg(PHP_BINARY);
    $cmd = $php.' -d display_errors=stderr vendor/bin/testbench demo:export';

    $piped = $this->pipeline("$cmd | $php -r 'echo count(json_decode(stream_get_contents(STDIN), true));'");
    expect($piped['stdout'])->toBe('2')
        ->and($piped['stderr'])->toContain('Fetching rows');

    $silenced = $this->pipeline("$cmd 2>/dev/null");
    expect(json_decode($silenced['stdout'], true))->toHaveCount(2);

    $merged = $this->pipeline("$cmd 2>&1 >/dev/null");
    expect($merged['stdout'])->toContain('Exporting users')->not->toContain('"id"');
});

it('supports conditional activation via shouldWriteDiagnosticsToStderr()', function () {
    $human = $this->cli(['demo:conditional']);
    expect($human['stderr'])->toBe('')
        ->and($human['stdout'])->toContain('working')->toContain('{"human":"readable"}');

    $json = $this->cli(['demo:conditional', '--json']);
    expect(json_decode($json['stdout'], true))->toBe(['ok' => true])
        ->and($json['stderr'])->toContain('working');
});

it('keeps prompts off STDOUT and respects --no-interaction defaults', function () {
    $r = $this->cli(['demo:prompt', '--no-interaction']);

    expect(json_decode($r['stdout'], true))->toBe(['confirmed' => true, 'name' => 'anon'])
        ->and($r['exit'])->toBe(0);
});
