<?php

namespace Ziegen2801\DockerRuntime;

use Illuminate\Support\ServiceProvider;
use Ziegen2801\DockerRuntime\Console\InstallDockerCommand;

class DockerRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallDockerCommand::class,
            ]);
        }
    }
}
