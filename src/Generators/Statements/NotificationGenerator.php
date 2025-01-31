<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Tree;

class NotificationGenerator extends StatementGenerator
{
    protected $new_instance = 'new message instance';

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->filesystem->stub('notification.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof SendStatement) {
                        continue;
                    }

                    if (!$statement->isNotification()) {
                        continue;
                    }

                    $path = $this->getPath($statement->mail());

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    if (!$this->filesystem->exists(dirname($path))) {
                        $this->filesystem->makeDirectory(dirname($path), 0755, true);
                    }

                    $this->filesystem->put($path, $this->populateStub($stub, $statement));

                    $output['created'][] = $path;
                }
            }
        }

        return $output;
    }

    public function types(): array
    {
        return ['controllers'];
    }

    protected function getPath(string $name)
    {
        return Blueprint::appPath() . '/Notification/' . $name . '.php';
    }

    protected function populateStub(string $stub, SendStatement $sendStatement)
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Notification', $stub);
        $stub = str_replace('{{ class }}', $sendStatement->mail(), $stub);
        $stub = str_replace('{{ properties }}', $this->buildConstructor($sendStatement), $stub);

        if (Blueprint::supportsReturnTypeHits()) {
            $stub = str_replace(
                [
                    'via($notifiable)',
                    'toMail($notifiable)',
                    'toArray($notifiable)',
                ],
                [
                    'via($notifiable): array',
                    sprintf('toMail($notifiable): %s', $sendStatement->mail()),
                    'toArray($notifiable): array'
                ],
                $stub
            );
        }

        return $stub;
    }
}
