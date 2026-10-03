<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Cortex\Domains\McpServer\Events\McpInstructionVersionCreatedActionEvent;
use JayI\Cortex\Domains\McpServer\Events\McpInstructionVersionCreatingActionEvent;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Support\PublicationCache;

final class CreateMcpInstructionVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'publish' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(string $server, array $data): McpInstructionVersionModel
    {
        McpInstructionVersionCreatingActionEvent::dispatch($server, $data);

        $result = $this->perform($server, $data);

        McpInstructionVersionCreatedActionEvent::dispatch($server, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(string $server, array $data): McpInstructionVersionModel
    {
        return DB::transaction(function () use ($server, $data): McpInstructionVersionModel {
            /** @var McpInstructionModel $instruction */
            $instruction = McpInstructionModel::query()->firstOrCreate(['server' => $server]);

            /** @var McpInstructionVersionModel $version */
            $version = $instruction->versions()->create([
                'version' => ((int) $instruction->versions()->lockForUpdate()->max('version')) + 1,
                'content' => $data['content'],
            ]);

            if ($data['publish'] ?? false) {
                $instruction->published_version_id = $version->getKey();
                $instruction->save();

                $this->cache->forget($this->cache->mcpInstructionsKey());
            }

            return $version;
        });
    }
}
