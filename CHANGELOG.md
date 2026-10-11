# Changelog

All notable changes to `mohannadelemary/laravel-console-streams` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- `Concerns\WritesDiagnosticsToStderr` opt-in trait: `$this->components->*`, `line()`, `info()`, `comment()`, `warn()`, `error()`, `table()`, `newLine()` and prompts write to STDERR; data goes to STDOUT.
- `writeJson()`, `writeStdout()` and `stdout()` helpers (raw, undecorated, printed even with `--quiet`).
- `shouldWriteDiagnosticsToStderr()` to enable the redirect conditionally (e.g. only with `--json`).
- `diagnosticsFallback()` (`passthrough`, `discard`, `throw`) for outputs without a separate error stream.
- Real-subprocess Pest test suite; CI matrix for Laravel 12/13 on PHP 8.3-8.5.
