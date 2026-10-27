<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionDeletedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\McpInstructionDeletingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;
use Throwable;

/**
 * Deletes a server's instructions override: every version, one model at a
 * time (each fires its model events), and the published pointer. The
 * override row itself goes only when it holds no tool override, so a saved
 * tool list keeps applying.
 *
 * Returns the row when it survives (it still holds a tool override), or null
 * when it was removed, so callers can answer with what is left.
 */
final class DeleteMcpInstructionAction extends Action
{
    public function __construct(private readonly PublicationCache $cache) {}

    protected function handle(McpInstructionModel $instruction): ?McpInstructionModel
    {
        McpInstructionDeletingActionEvent::dispatch($instruction);

        $surviving = $this->perform($instruction);

        McpInstructionDeletedActionEvent::dispatch($instruction);

        return $surviving;
    }

    private function perform(McpInstructionModel $instruction): ?McpInstructionModel
    {
        return DB::transaction(function () use ($instruction): ?McpInstructionModel {
            // Decided from the locked row, never from the copy passed in, so a
            // tool list saved after the action started is not lost.
            /** @var McpInstructionModel|null $locked */
            $locked = $instruction->newQuery()->whereKey($instruction->getKey())->lockForUpdate()->first();

            if ($locked !== null) {
                /** @var McpInstructionVersionModel $version */
                foreach ($locked->versions()->lazyById() as $version) {
                    $version->delete();
                }

                if ($locked->tools === null) {
                    $locked->delete();
                    $locked = null;
                } elseif ($locked->published_version_id !== null) {
                    $locked->published_version_id = null;
                    $locked->save();
                }
            }

            DB::afterCommit(fn () => $this->forget($this->cache->mcpInstructionsKey(), $this->cache->mcpToolsKey()));

            return $locked;
        });
    }

    /**
     * Forget cache keys after commit without failing the committed delete:
     * a store that throws is logged, and the old value is served until the
     * key expires or the next forget succeeds.
     */
    private function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            try {
                $this->cache->forget($key);
            } catch (Throwable $exception) {
                Log::warning("Cortex deleted server instructions but could not clear the cached [{$key}]: the previous value is served until the key expires or the next change clears it.", [
                    'key' => $key,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
