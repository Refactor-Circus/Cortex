<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;

final class CreateMcpInstructionVersionAction extends Action
{
    public function __construct(private readonly PublicationCache $cache) {}

    protected function handle(string $server, CreateMcpInstructionVersionData $data): McpInstructionVersionModel
    {
        McpInstructionVersionCreatingActionEvent::dispatch($server, $data);

        $result = $this->perform($server, $data);

        McpInstructionVersionCreatedActionEvent::dispatch($server, $result);

        return $result;
    }

    private function perform(string $server, CreateMcpInstructionVersionData $data): McpInstructionVersionModel
    {
        return DB::transaction(function () use ($server, $data): McpInstructionVersionModel {
            /** @var McpInstructionModel $instruction */
            $instruction = McpInstructionModel::query()->firstOrCreate(['server' => $server]);

            // Lock the parent row, not the aggregate: PostgreSQL refuses FOR UPDATE with MAX().
            $instruction->newQuery()->whereKey($instruction->getKey())->lockForUpdate()->value($instruction->getKeyName());

            /** @var McpInstructionVersionModel $version */
            $version = $instruction->versions()->create([
                'version' => ((int) $instruction->versions()->max('version')) + 1,
                'content' => $data->content,
            ]);

            if ($data->publish) {
                $instruction->published_version_id = $version->getKey();
                $instruction->save();

                DB::afterCommit(fn () => $this->cache->forget($this->cache->mcpInstructionsKey()));
            }

            return $version;
        });
    }
}
