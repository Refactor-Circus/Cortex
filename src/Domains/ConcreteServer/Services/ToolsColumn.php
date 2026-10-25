<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;

/**
 * Whether the override table has its `tools` column. Cortex only publishes
 * its migrations, so a host that upgraded without running the tools
 * migration has none; serving then treats every server as having no tool
 * override.
 *
 * The check asks the model's own connection (a tenant or non-default
 * connection may differ from the default one) and is remembered for the
 * process: the class is a singleton, so an Octane or queue worker checks
 * once and keeps the answer until it restarts. Only a completed check is
 * remembered.
 */
final class ToolsColumn
{
    /**
     * @var array<string, bool>
     */
    private array $exists = [];

    public function exists(): bool
    {
        $model = new McpInstructionModel;
        $connection = $model->getConnection();
        $table = $model->getTable();

        return $this->exists[(string) $connection->getName()."\0".$table] ??= $connection->getSchemaBuilder()->hasColumn($table, 'tools');
    }
}
