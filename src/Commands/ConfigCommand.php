<?php

namespace Blueprint\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

class ConfigCommand extends Command
{

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:config {--reset} {--no-interact}';

    /**
     * @param Filesystem $files
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Configure Blueprint for the project.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        if ($this->option('reset')) {
            $this->call('vendor:publish', [
                '--tag' => 'blueprint-config',
                '--force' => true,
            ]);
        }

        $this->replaceInFile(
            "'models_namespace' => ''",
            "'models_namespace' => 'Models'",
            config_path('blueprint.php')
        );

        if (! $this->option('no-interact')) {
            if ($namespace = $this->anticipate('Define a namespace for your controllers (for example “Api”) ou press enter to skip', ['Api'])) {
                $this->replaceInFile(
                    "'controllers_namespace' => 'Http\\\\Controllers'",
                    "'controllers_namespace' => 'Http\\\\Controllers\\\\" . $namespace . "'",
                    config_path('blueprint.php')
                );
            }

            if ($this->confirm('Generate PHPDocs?', true)) {
                $this->replaceInFile(
                    "'generate_phpdocs' => false",
                    "'generate_phpdocs' => true",
                    config_path('blueprint.php')
                );
            }

            if ($this->confirm('Add foreign key constraint?', true)) {
                $this->replaceInFile(
                    "'use_constraints' => false",
                    "'use_constraints' => true",
                    config_path('blueprint.php')
                );
            }

            if ($this->confirm('Enable method return typehinting?')) {
                $this->replaceInFile(
                    "'use_return_types' => false",
                    "'use_return_types' => true",
                    config_path('blueprint.php')
                );
            }

            if ($this->confirm('Use guarded instead of fillable?')) {
                $this->replaceInFile(
                    "'use_guarded' => false",
                    "'use_guarded' => true",
                    config_path('blueprint.php')
                );
            }
        } else {
            if (config('blueprint.enabled.api')) {
                $this->replaceInFile(
                    "'controllers_namespace' => 'Http\\\\Controllers'",
                    "'controllers_namespace' => 'Http\\\\Controllers\\\\Api'",
                    config_path('blueprint.php')
                );
            }

            $this->replaceInFile(
                "'generate_phpdocs' => false",
                "'generate_phpdocs' => true",
                config_path('blueprint.php')
            );

            $this->replaceInFile(
                "'use_constraints' => false",
                "'use_constraints' => true",
                config_path('blueprint.php')
            );
        }

        $this->replaceInFile(
            "'generate_fqcn_route' => false",
            "'generate_fqcn_route' => true",
            config_path('blueprint.php')
        );

        $stub = 'draft';
        if (config('blueprint.enabled.api')) {
            $stub .= '.api';
        }
        if (config('blueprint.enabled.user-management')) {
            $stub .= '.users';
            if (config('blueprint.enabled.organizations')) {
                $stub .= '.organizations';
            }
            if (config('blueprint.enabled.preferences')) {
                $stub .= '.preferences';
            }
        }
        $this->filesystem->put(
            base_path('draft.yaml'),
            $this->filesystem->stub("$stub.stub")
        );

        $command = file_exists(base_path('docker-compose.yaml')) ? 'sail' : 'php';

        $this->info('You’re good to go! Feel free to modify the draft.yaml file or just run:');
        if (config('blueprint.enabled.api') && config('blueprint.enabled.user-management')) {
            $this->comment("$command artisan blueprint:build && $command artisan migrate:fresh && $command artisan passport:install --force && $command artisan akkurate:user");
        } else {
            $this->comment("$command artisan blueprint:build && $command artisan migrate");
        }

        if (config('blueprint.enabled.api-docs')) {
            $this->newLine();
            $this->info('To generate API docs:');
            $this->comment("$command artisan scribe:generate");
        }
    }

    /**
     * Replace a given string within a given file.
     *
     * @param string $search
     * @param string $replace
     * @param string $path
     * @return string
     */
    protected function replaceInFile(string $search, string $replace, string $path): string
    {
        if ($search !== $replace) {
            file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));

            return "Content replaced: « $search » by « $replace »";
        } else {
            return "Unmodified content. « $replace » content not inserted";
        }
    }
}
