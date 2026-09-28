<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use Illuminate\Support\Facades\DB;
use JayI\Cortex\Events\Action\ConcreteAgentVersionCreatedActionEvent;
use JayI\Cortex\Events\Action\ConcreteAgentVersionCreatingActionEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;
use JayI\Cortex\Support\PublicationCache;

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
    public function execute(string $agent, array $data): ConcreteAgentOverrideVersion
    {
        ConcreteAgentVersionCreatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        ConcreteAgentVersionCreatedActionEvent::dispatch($agent, $result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function perform(string $agent, array $data): ConcreteAgentOverrideVersion
    {
        return DB::transaction(function () use ($agent, $data): ConcreteAgentOverrideVersion {
            /** @var ConcreteAgentOverride $override */
            $override = ConcreteAgentOverride::query()->firstOrCreate(['agent' => $agent]);

            /** @var ConcreteAgentOverrideVersion $version */
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
