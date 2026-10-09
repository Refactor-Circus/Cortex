<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionCreatedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionCreatingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class CreateConcreteAgentVersionAction
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
    public function execute(string $agent, array $data): ConcreteAgentOverrideVersionModel
    {
        ConcreteAgentVersionCreatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        ConcreteAgentVersionCreatedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(string $agent, array $data): ConcreteAgentOverrideVersionModel
    {
        return DB::transaction(function () use ($agent, $data): ConcreteAgentOverrideVersionModel {
            /** @var ConcreteAgentOverrideModel $override */
            $override = ConcreteAgentOverrideModel::query()->firstOrCreate(['agent' => $agent]);

            /** @var ConcreteAgentOverrideVersionModel $version */
            $version = $override->versions()->create([
                'version' => ((int) $override->versions()->lockForUpdate()->max('version')) + 1,
                'content' => $data['content'],
            ]);

            if ($data['publish'] ?? false) {
                $override->published_version_id = (string) $version->getKey();
                $override->save();

                $this->cache->forget($this->cache->concreteAgentsKey());
            }

            return $version;
        });
    }
}
