<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Model;
use Blueprint\Models\Statements\ResourceStatement;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ResourceGenerator implements Generator
{
    const INDENT = '            ';

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

        $stub = $this->filesystem->stub('resource.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof ResourceStatement) {
                        continue;
                    }

                    $path = $this->getPath(($controller->namespace() ? $controller->namespace() . '/' : '') . $statement->name());

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    if (!$this->filesystem->exists(dirname($path))) {
                        $this->filesystem->makeDirectory(dirname($path), 0755, true);
                    }

                    $this->filesystem->put($path, $this->populateStub($stub, $controller, $statement));

                    $output['created'][] = $path;
                }
            }
        }

        return $output;
    }

    public function types(): array
    {
        return ['controllers', 'resources'];
    }

    protected function getPath(string $name)
    {
        return Blueprint::appPath() . '/Http/Resources/' . $name . '.php';
    }

    protected function populateStub(string $stub, Controller $controller, ResourceStatement $resource)
    {
        $namespace = config('blueprint.namespace')
            . '\\Http\\Resources'
            . ($controller->namespace() ? '\\' . $controller->namespace() : '');

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ import }}', $resource->collection() ? 'Illuminate\\Http\\Resources\\Json\\ResourceCollection' : 'Illuminate\\Http\\Resources\\Json\\JsonResource', $stub);
        $stub = str_replace('{{ parentClass }}', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('{{ class }}', $resource->name(), $stub);
        $stub = str_replace('{{ parentClass }}', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('{{ resource }}', $resource->collection() ? 'resource collection' : 'resource', $stub);
        $stub = str_replace('{{ body }}', $this->buildData($resource), $stub);

        if (Blueprint::supportsReturnTypeHits()) {
            $stub = str_replace('toArray($request)', 'toArray($request): array', $stub);
        }
        return $stub;
    }

    protected function buildData(ResourceStatement $resource)
    {
        $context = Str::singular($resource->reference());

        /**
         * @var \Blueprint\Models\Model $model
         */
        $model = $this->tree->modelForContext($context);

        $data = [];
        if ($resource->collection()) {
            $data[] = 'return [';
            $data[] = self::INDENT . '\'data\' => $this->collection,';
            $data[] = '        ];';

            return implode(PHP_EOL, $data);
        }

        $data[] = 'return [';
        foreach ($this->visibleColumns($model) as $column) {
            $data[] = self::INDENT . '\'' . $column . '\' => $this->' . $column . ',';
        }
        foreach ($this->belongsToRelations($model) as $modelName => $relation) {
            $data[] = $this->writeResourceRelation($modelName, $relation);
        }
        foreach ($this->hasManyRelations($model) as $modelName => $relation) {
            $data[] = $this->writeCollectionRelation($modelName, $relation);
        }
        foreach ($this->belongsToManyRelations($model) as $modelName => $relation) {
            $data[] = $this->writeCollectionRelation($modelName, $relation);
        }
        $data[] = '        ];';

        return implode(PHP_EOL, $data);
    }

    private function writeResourceRelation($modelName, $relation): string
    {
        if (Str::contains($relation, ':')) {
            [$foreign_reference, $column_name] = explode(':', $relation);
            return self::INDENT . '\'' . $column_name . '\' => new ' . Str::studly($foreign_reference) . 'Resource($this->whenLoaded(\'' . Str::camel($column_name) . '\')),';
        } else {
            return self::INDENT . '\'' . $relation . '\' => new ' . $modelName . 'Resource($this->whenLoaded(\'' . Str::camel($relation) . '\')),';
        }
    }

    private function writeCollectionRelation($modelName, $relation): string
    {
        if (Str::contains($relation, ':')) {
            [$foreign_reference, $column_name] = explode(':', $relation);
            return self::INDENT . '\'' . $column_name . '\' => ' . Str::studly($foreign_reference) . 'Resource::collection($this->whenLoaded(\'' . Str::camel($column_name) . '\')),';
        } else {
            return self::INDENT . '\'' . $relation . '\' => ' . $modelName . 'Resource::collection($this->whenLoaded(\'' . Str::camel($relation) . '\')),';
        }
    }

    private function visibleColumns(Model $model)
    {
        return array_diff(
            array_keys($model->columns()),
            [
                'password',
                'remember_token',
            ]
        );
    }

    private function hasManyRelations(Model $model): array
    {
        $columns = [];

        if (!empty($model->relationships())) {
            if (isset($model->relationships()['hasMany'])) {
                foreach ($model->relationships()['hasMany'] as $relationship) {
                    $columns[$relationship] = Str::snake(Str::plural($relationship));
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
            }
        }

        return $columns;
    }

    private function belongsToManyRelations(Model $model): array
    {
        $columns = [];

        if (isset($model->relationships()['belongsToMany'])) {
            foreach ($model->relationships()['belongsToMany'] as $relationship) {
                $columns[$relationship] = Str::snake(Str::plural($relationship));
            }
        }

        return $columns;
    }
}
