<?php

namespace Ziegen28\DockerRuntime;

use Illuminate\Support\ServiceProvider;
use Ziegen28\DockerRuntime\Console\InstallDockerCommand;

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
