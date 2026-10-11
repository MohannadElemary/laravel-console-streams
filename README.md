# Laravel Console Streams

[![tests](https://github.com/MohannadElemary/laravel-console-streams/actions/workflows/tests.yml/badge.svg)](https://github.com/MohannadElemary/laravel-console-streams/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

An **opt-in** trait for Artisan commands that keeps **machine data on STDOUT** and sends
**everything else on STDERR** — `components->task()`, `components->info()`, `line()`, `table()`, prompts and friends —
so this just works:

```bash
php artisan users:export --json | jq '.[0]'
php artisan users:export --json > users.json   # progress still shows in your terminal
```

It exists because of [laravel/framework#56602](https://github.com/laravel/framework/issues/56602): console
components always write to STDOUT, so a `task()` line ends up inside your JSON. Three attempts to change that in the
framework ([#57293](https://github.com/laravel/framework/pull/57293), [#58727](https://github.com/laravel/framework/pull/58727),
[#60118](https://github.com/laravel/framework/pull/60118)) were closed — mainly because moving *all* component output
to STDERR globally is a breaking change, and because components are also used for *successful results*, not only progress.
This package is the package-shaped answer: nothing global, only the commands you opt in.

## Installation

```bash
composer require mohannadelemary/laravel-console-streams
```

Requires PHP 8.3+ and Laravel 12 or 13. No service provider, no config.

## Usage

```php
use Illuminate\Console\Command;
use MohannadElemary\ConsoleStreams\Concerns\WritesDiagnosticsToStderr;

class ExportUsers extends Command
{
    use WritesDiagnosticsToStderr;

    protected $signature = 'users:export {--json}';

    public function handle(): int
    {
        $this->components->info('Exporting users');                         // STDERR
        $this->components->task('Fetching rows', fn () => $this->fetch());  // STDERR
        $this->table(['id', 'name'], $rows);                                // STDERR
        $this->warn('2 rows skipped');                                      // STDERR

        $this->writeJson($rows);                                            // STDOUT

        return self::SUCCESS;
    }
}
```

### Demo (real output from the test workbench)

```console
$ php artisan demo:export 2>/dev/null | jq -c '.[].name'
"Alice <info>tag</info>"
"Böb/ü"

$ php artisan demo:export 2>&1 >/dev/null      # what lands on STDERR
   INFO  Exporting users.

  Fetching rows .................................................. 0.03ms DONE

   WARN  2 rows skipped.
...
```

### API

| Method | Purpose |
| --- | --- |
| `writeJson($data, $flags = PRETTY_PRINT\|UNESCAPED_SLASHES\|UNESCAPED_UNICODE)` | Encode and write to STDOUT (throws on invalid data). |
| `writeStdout(string $data, bool $newline = true)` | Raw write to STDOUT: no `<tag>` parsing, no ANSI, **printed even with `--quiet`**. |
| `stdout()` | The original STDOUT-backed `OutputStyle`, for anything else you need on STDOUT. |
| `shouldWriteDiagnosticsToStderr(): bool` | Override to activate conditionally, e.g. `return (bool) $this->option('json');`. Default `true`. |
| `diagnosticsFallback(): string` | `'passthrough'` (default), `'discard'` or `'throw'` — see below. |

Redirect only in JSON mode, so your human-facing output is unchanged otherwise:

```php
protected function shouldWriteDiagnosticsToStderr(): bool
{
    return (bool) $this->option('json');
}
```

## How it works (and why it is not fragile)

Symfony runs `Command::initialize()` after Laravel has built `$this->output` / `$this->components` and after the input is bound.
The trait implements that public lifecycle hook and, for that one command only, replaces the two properties with an
`Illuminate\Console\OutputStyle` and `View\Components\Factory` backed by `ConsoleOutput::getErrorOutput()`.
No framework class is extended, overridden, macro'd or monkey-patched, and no other command is affected.
A control command without the trait is part of the test suite and must keep writing everything to STDOUT.

Because the error output is Symfony's own, behaviour is inherited rather than re-implemented:

- **ANSI** — decided per stream (STDERR can be colourised while STDOUT is piped); `--ansi` / `--no-ansi` apply to both.
- **Verbosity** — `-q`, `-v`, `-vv`, `-vvv` apply (`components->info($msg, OutputInterface::VERBOSITY_VERBOSE)` etc.).
  With `--quiet` diagnostics are silent but data from `writeJson()`/`writeStdout()` is still printed.
- **Non-interactive** — `--no-interaction` still returns defaults; prompts and questions are pointed at STDERR (verified non-interactively only; interactive TTY prompts are not covered by the automated tests).
- **Exit codes** — untouched.

## Unsupported / caveats

- **No STDERR in the environment** → `Artisan::call()`, `$this->artisan()` in tests, queued/scheduled invocations with a
  `BufferedOutput` have no second stream. The default `'passthrough'` leaves the command exactly as it would be without the
  trait (data and diagnostics share one stream). Return `'discard'` from `diagnosticsFallback()` to drop diagnostics,
  or `'throw'` to get a `DiagnosticStreamUnavailable` exception. The package never silently pretends the streams are separate.
- **Your own `initialize()`** — if the command defines one, call `$this->initializeDiagnosticStreams($input, $output)` from it.
- **Things that write directly** to `STDOUT`/`echo`/`print`, `$this->output` captured *before* `initialize()` (e.g. in a constructor),
  other processes (`Process::run()` with `->tty()`), and third-party code that grabs the original output are not redirected.
  Use `$this->stdout()` deliberately when you want STDOUT.
- **`$this->output` now points at STDERR.** Code inside the command that does `$this->output->writeln(json_encode(...))` will go to STDERR;
  switch it to `writeJson()` / `writeStdout()`.
- **Laravel Prompts** (`Laravel\Prompts\Prompt::setOutput()`) is pointed at the error stream for the process so questions don't pollute data.
  That is a static, so it is process-wide *after* a trait-using command has started; in a normal `php artisan` process that is one command.
- Symfony *progress bars* created with `$this->output->createProgressBar()` use the command's `$this->output`, so they are expected to render on STDERR as well (not covered by tests).
- Termwind/Windows: not tested on Windows.
- A framework that later routes components to STDERR natively would make this package redundant — it will say so here when that happens.

## Testing

```bash
composer install
vendor/bin/pest      # includes real subprocess tests (separate STDOUT/STDERR pipes, shell pipes)
vendor/bin/pint --test
```

The suite uses [Orchestra Testbench](https://github.com/orchestral/testbench) with a workbench of demo commands and launches real
`testbench` (Artisan) processes, because `Artisan::call()` cannot prove OS-level stream separation.

## Contributing / License

Issues and PRs are welcome. The MIT License (MIT) — see [LICENSE](LICENSE).
