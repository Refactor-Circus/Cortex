<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsClearedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Events\ConcreteServerToolsClearingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;
use Throwable;

/**
 * Clears a concrete MCP server's tool override, so it serves its code tools
 * again. Instructions, versions and the published pointer are never touched;
 * the override row goes only when it holds nothing else. Clearing a server
 * without a tool override does nothing.
 */
final class ClearConcreteServerToolsAction extends Action
{
    public function __construct(
        private readonly PublicationCache $cache,
        private readonly McpServerRegistry $servers,
    ) {}

    protected function handle(string $server, ClearConcreteServerToolsData $data): void
    {
        ConcreteServerToolsClearingActionEvent::dispatch($server, $data);

        $this->perform($server);

        ConcreteServerToolsClearedActionEvent::dispatch($server);
    }

    private function perform(string $server): void
    {
        if (! $this->servers->supportsToolOverrides($server)) {
            throw ValidationException::withMessages([
                'server' => "The tools of the [{$server}] server cannot be overridden.",
            ]);
        }

        DB::transaction(function () use ($server): void {
            // Decided from the locked row, so a version created or published
            // after the action started keeps the row.
            /** @var McpInstructionModel|null $instruction */
            $instruction = McpInstructionModel::query()->where('server', $server)->lockForUpdate()->first();

            if ($instruction === null || $instruction->tools === null) {
                return;
            }

            $keys = [$this->cache->mcpToolsKey()];

            if ($instruction->published_version_id === null && ! $instruction->versions()->exists()) {
                $instruction->delete();

                $keys[] = $this->cache->mcpInstructionsKey();
            } else {
                $instruction->tools = null;
                $instruction->save();
            }

            DB::afterCommit(fn () => $this->forget(...$keys));
        });
    }

    /**
     * Forget cache keys after commit without failing the committed write:
     * a store that throws is logged, and the old value is served until the
     * key expires or the next forget succeeds.
     */
    private function forget(string ...$keys): void
    {
        foreach ($keys as $key) {
            try {
                $this->cache->forget($key);
            } catch (Throwable $exception) {
                Log::warning("Cortex cleared a server tool override but could not clear the cached [{$key}]: the previous value is served until the key expires or the next change clears it.", [
                    'key' => $key,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
