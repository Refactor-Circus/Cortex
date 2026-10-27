<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsUpdatedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsUpdatingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;
use Throwable;

/**
 * Stores the tool list a concrete MCP server serves instead of its code
 * tools, on the server's override row, creating the row when it is missing.
 * The `cortex` server's tools cannot be overridden.
 */
final class UpdateConcreteServerToolsAction extends Action
{
    public function __construct(
        private readonly PublicationCache $cache,
        private readonly McpServerRegistry $servers,
    ) {}

    protected function handle(string $server, UpdateConcreteServerToolsData $data): McpInstructionModel
    {
        ConcreteServerToolsUpdatingActionEvent::dispatch($server, $data);

        $result = $this->perform($server, $data);

        ConcreteServerToolsUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(string $server, UpdateConcreteServerToolsData $data): McpInstructionModel
    {
        if (! $this->servers->supportsToolOverrides($server)) {
            throw ValidationException::withMessages([
                'server' => "The tools of the [{$server}] server cannot be overridden.",
            ]);
        }

        try {
            return $this->save($server, $data);
        } catch (UniqueConstraintViolationException) {
            // A concurrent first save created the row after our read: its
            // transaction is gone, so save again, now onto that row.
            return $this->save($server, $data);
        }
    }

    private function save(string $server, UpdateConcreteServerToolsData $data): McpInstructionModel
    {
        return DB::transaction(function () use ($server, $data): McpInstructionModel {
            // Decide create or update from the locked row, never from a read
            // made before the transaction.
            /** @var McpInstructionModel $instruction */
            $instruction = McpInstructionModel::query()->where('server', $server)->lockForUpdate()->first()
                ?? new McpInstructionModel(['server' => $server]);

            $instruction->tools = $data->tools;
            $instruction->save();

            DB::afterCommit(fn () => $this->forget($this->cache->mcpToolsKey()));

            return $instruction;
        });
    }

    /**
     * Forget a cache key after commit without failing the committed write:
     * a store that throws is logged, and the old value is served until the
     * key expires or the next forget succeeds.
     */
    private function forget(string $key): void
    {
        try {
            $this->cache->forget($key);
        } catch (Throwable $exception) {
            Log::warning("Cortex saved a server tool list but could not clear the cached [{$key}]: the previous list is served until the key expires or the next change clears it.", [
                'key' => $key,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
