<?php

namespace Blueprint\Generators\Typescript;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TypescriptServiceGenerator implements Generator
{
    private const INDENT = '  ';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Tree
     */
    private $tree;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $output = [];

        $stub = $this->filesystem->stub('typescript.service.stub');

        /**
         * @var \Blueprint\Models\Model $model
         */
        foreach ($tree->models() as $model) {
            $path = $this->getPath($model->name());

            if (!$this->filesystem->exists(storage_path('app/export/services/'))) {
                $this->filesystem->makeDirectory(storage_path('app/export/services/'), 0755, true);
            }

            $this->filesystem->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return [];
    }

    protected function getPath(string $name)
    {
        return storage_path('app/export/services/') . Str::camel(Str::plural($name)) . '.service.ts';
    }

    protected function populateStub(string $stub, $model)
    {
        $stub = str_replace(
            [
                '{{modelCamel}}',
                '{{modelStudly}}',
                '{{modelCamelPlural}}',
                '{{modelKebabPlural}}',
                '{{modelStudlyPlural}}'

            ],
            [
                Str::camel($model->name()),
                Str::studly($model->name()),
                Str::camel(Str::plural($model->name())),
                Str::kebab(Str::plural($model->name())),
                Str::studly(Str::plural($model->name())),
            ], $stub);
        return $stub;
    }

}
