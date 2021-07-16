<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TypescriptInterfaceGenerator implements Generator
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

    private $imports = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $output = [];

        $stub = $this->filesystem->stub('typescript.interface.stub');

        /**
         * @var \Blueprint\Models\Model $model
         */
        foreach ($tree->models() as $model) {
            $path = $this->getPath($model->name());

            if (!$this->filesystem->exists(storage_path('app/export/interfaces/'))) {
                $this->filesystem->makeDirectory(storage_path('app/export/interfaces/'), 0755, true);
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
        return storage_path('app/export/interfaces/') . Str::camel($name) . '.ts';
    }

    protected function populateStub(string $stub, $model)
    {
        $stub = str_replace('{{ model }}', $model->name(), $stub);
        $stub = str_replace('//', $this->buildData($model), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($model, $stub), $stub);

        return $stub;
    }

    protected function buildData(Model $model)
    {
        $definition = '';

        /**
         * @var \Blueprint\Models\Column $column
         */
        foreach ($model->columns() as $column) {

            if ($column->name() === 'id' || $column->name() === 'password' || $column->name() === 'remember_token') {
                continue;
            }

            $columnName = Str::camel($column->name());

            if ($column->dataType() === 'morphs') {

                $definition .= sprintf('%s%s: %s%s', self::INDENT, "{$columnName}Id", 'number', PHP_EOL);
                $definition .= sprintf('%s%s: %s%s', self::INDENT, "{$columnName}Type", 'string', PHP_EOL);

            } else {

                $dataType = $column->dataType();
                if ($dataType === 'id' || $dataType === 'integer' || $dataType === 'decimal' || $dataType === 'float') {
                    $dataType = 'number';
                }
                if ($dataType === 'text') {
                    $dataType = 'string';
                }
                if ($dataType === 'json') {
                    $dataType = 'object';
                }
                if ($dataType === 'timestamp' || $dataType === 'date') {
                    $dataType = 'Date';
                }
                if ($dataType === 'enum') {
                    $dataType = 'string';
                }
                $definition .= self::INDENT . "$columnName: ";
                $definition .= $dataType;
                $definition .= $column->isNullable() ? ' | null' : '';
                $definition .= PHP_EOL;

            }
        }

        foreach ($this->belongsToRelations($model) as $modelName => $relation) {

            if (Str::contains($relation, ':')) {
                $reference = Str::beforeLast($relation, ':');
                $columnName = Str::afterLast($relation, ':');
                $definition .= self::INDENT . Str::camel($columnName) . ': Interface' . Str::studly($reference);
                $definition .= $column->isNullable() ? ' | null' : '';
                $definition .= PHP_EOL;
            } else {
                $definition .= self::INDENT . Str::camel($relation) . ': Interface' . Str::studly($relation);
                $definition .= $column->isNullable() ? ' | null' : '';
                $definition .= PHP_EOL;
            }

        }

        foreach ($this->hasManyRelations($model) as $modelName => $relation) {
            $definition .= self::INDENT . Str::camel($relation) . ': object';
            $definition .= $column->isNullable() ? ' | null' : '';
            $definition .= PHP_EOL;
        }

        return trim($definition);
    }

    private function hasManyRelations(Model $model): array
    {
        $columns = [];

        if (!empty($model->relationships())) {
            if (isset($model->relationships()['hasMany'])) {
                foreach ($model->relationships()['hasMany'] as $relationship) {
                    $columns[$relationship] = Str::plural(Str::lower($relationship));
                }
            }
        }

        return $columns;
    }

    private function belongsToRelations(Model $model): array
    {
        $columns = [];

        if (isset($model->relationships()['belongsTo'])) {
            foreach ($model->relationships()['belongsTo'] as $relationship) {
                $column = Str::beforeLast($relationship, '_id');
                $columns[Str::studly($column)] = $column;
                $this->imports[$model->name()][] = $column;
            }
        }

        return $columns;
    }

    private function buildImports($model, $stub): string
    {
        $data = '';

        if (isset($this->imports[$model->name()])) {
            $imports = array_unique($this->imports[$model->name()]);

            foreach ($imports as $import)
            {
                if (Str::contains($import, ':')) {
                    $reference = Str::beforeLast($import, ':');
                    if (! strpos($data, 'import { Interface' . Str::studly($reference))) {
                        $data .= 'import { Interface' . Str::studly($reference) . ' } from '. "'~/interfaces/" . Str::camel($reference) . "'" . PHP_EOL;
                    }
                } else {
                    if (! strpos($data, 'import { Interface' . Str::studly($import))) {
                        $data .= 'import { Interface' . Str::studly($import) . ' } from '. "'~/interfaces/" . Str::camel($import) . "'" . PHP_EOL;
                    }

                }
            }
            $data .= PHP_EOL;
        }

        return $data;
    }

}
