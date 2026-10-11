<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Console\Commands\ConditionalCommand;
use Workbench\App\Console\Commands\ControlCommand;
use Workbench\App\Console\Commands\ExportCommand;
use Workbench\App\Console\Commands\FallbackCommand;
use Workbench\App\Console\Commands\PromptCommand;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ExportCommand::class, ConditionalCommand::class, ControlCommand::class, PromptCommand::class, FallbackCommand::class]);
        }
    }
}
