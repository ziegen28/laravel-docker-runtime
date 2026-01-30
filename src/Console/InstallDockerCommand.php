<?php

namespace Ziegen28\DockerRuntime\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallDockerCommand extends Command
{
    protected $signature = 'docker:install {--silent} {--preset=}';
    protected $description = 'Install Sail++ Ultimate Docker Runtime';

    public function handle(): void
    {
        $this->info('🐳 Sail++ Ultimate Docker Runtime');

        // -------------------------------------------------
        // UID / GID (Linux / WSL safe)
        // -------------------------------------------------
        $uid = function_exists('posix_getuid') ? posix_getuid() : 1000;
        $gid = function_exists('posix_getgid') ? posix_getgid() : 1000;

        // -------------------------------------------------
        // Configuration
        // -------------------------------------------------
        $preset = $this->option('preset');
        $config = [];

        if ($this->option('silent')) {
            $config = [
                'php'      => '8.4',
                'engine'   => 'apache',
                'db'       => 'mysql',
                'adminer' => true,
                'redis'   => false,
                'mongo'   => false,
                'meili'   => false,
            ];
        } elseif ($preset === 'team') {
            $config = [
                'php'      => '8.4',
                'engine'   => 'apache',
                'db'       => 'mysql',
                'adminer' => true,
                'redis'   => true,
                'mongo'   => true,
                'meili'   => true,
            ];
        } else {
            // -----------------------------
            // Interactive Wizard
            // -----------------------------
            $config['php'] = substr(
                $this->choice(
                    'PHP version',
                    ['8.4 (Recommended)', '8.3', '8.2'],
                    0
                ),
                0,
                3
            );

            // Apache ONLY (Nginx later)
            $config['engine'] = 'apache';

            $dbChoice = $this->choice(
                'Database',
                ['MySQL', 'PostgreSQL', 'SQLite'],
                0
            );

            $config['db'] = match ($dbChoice) {
                'PostgreSQL' => 'pgsql',
                'SQLite'    => 'sqlite',
                default     => 'mysql',
            };

            $config['adminer'] = $this->confirm('Enable Adminer?', true);
            $config['redis']   = $this->confirm('Enable Redis?', false);
            $config['mongo']   = $this->confirm('Enable MongoDB?', false);
            $config['meili']   = $this->confirm('Enable Meilisearch?', false);
        }

        // -------------------------------------------------
        // Generate Dockerfile
        // -------------------------------------------------
        File::put(
            base_path('Dockerfile'),
            str_replace(
                ['{{PHP}}', '{{UID}}', '{{GID}}'],
                [$config['php'], $uid, $gid],
                File::get(
                    __DIR__ . '/../../stubs/dockerfiles/php-apache.stub'
                )
            )
        );

        // -------------------------------------------------
        // Apache VirtualHost
        // -------------------------------------------------
        File::ensureDirectoryExists(base_path('docker/apache'));

        File::put(
            base_path('docker/apache/dev-site.conf'),
            File::get(__DIR__ . '/../../stubs/apache/dev-site.conf.stub')
        );

        // -------------------------------------------------
        // docker-compose.yml
        // -------------------------------------------------
        $compose = str_replace(
            ['{{UID}}', '{{GID}}'],
            [$uid, $gid],
            File::get(__DIR__ . '/../../stubs/docker-compose.base.stub')
        );

        // Database
        $compose .= File::get(
            __DIR__ . '/../../stubs/databases/' . $config['db'] . '.stub'
        );

        // Optional services
        if ($config['adminer']) {
            $compose .= File::get(__DIR__ . '/../../stubs/services/adminer.stub');
        }

        if ($config['redis']) {
            $compose .= File::get(__DIR__ . '/../../stubs/services/redis.stub');
        }

        if ($config['mongo']) {
            $compose .= File::get(__DIR__ . '/../../stubs/services/mongo.stub');
        }

        if ($config['meili']) {
            $compose .= File::get(__DIR__ . '/../../stubs/services/meili.stub');
        }

        File::put(base_path('docker-compose.yml'), rtrim($compose) . PHP_EOL);

        // -------------------------------------------------
        // AUTO-SYNC .env (DB + runtime safety)
        // -------------------------------------------------
        if ($config['db'] === 'sqlite') {
            $this->updateEnv([
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE'   => '/var/www/html/database/database.sqlite',
            ]);
        } elseif ($config['db'] === 'pgsql') {
            $this->updateEnv([
                'DB_CONNECTION' => 'pgsql',
                'DB_HOST'       => 'db',
                'DB_PORT'       => '5432',
                'DB_DATABASE'   => 'laravel',
                'DB_USERNAME'   => 'laravel',
                'DB_PASSWORD'   => 'secret',
            ]);
        } else {
            $this->updateEnv([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST'       => 'db',
                'DB_PORT'       => '3306',
                'DB_DATABASE'   => 'laravel',
                'DB_USERNAME'   => 'laravel',
                'DB_PASSWORD'   => 'secret',
            ]);
        }

        // Laravel safety defaults (VERY IMPORTANT)
        $this->updateEnv([
            'SESSION_DRIVER'   => 'file',
            'CACHE_DRIVER'     => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ]);

        $this->info('✅ Sail++ Ultimate runtime generated');
        $this->line('Next: docker compose up -d --build');
    }

    // -------------------------------------------------
    // .env updater (safe, idempotent)
    // -------------------------------------------------
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->warn('.env file not found, skipping env sync.');
            return;
        }

        $env = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*$/m";

            if (preg_match($pattern, $env)) {
                $env = preg_replace($pattern, "{$key}={$value}", $env);
            } else {
                $env .= PHP_EOL . "{$key}={$value}";
            }
        }

        file_put_contents($envPath, trim($env) . PHP_EOL);

        $this->info('🔧 .env synced with Docker configuration');
    }
}
