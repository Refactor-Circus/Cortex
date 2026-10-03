<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Actions;

use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionPublishedActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionPublishingActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use JayI\Cortex\Support\PublicationCache;

final class PublishConcreteAgentVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(ConcreteAgentOverrideModel $override, int $version): ConcreteAgentOverrideModel
    {
        ConcreteAgentVersionPublishingActionEvent::dispatch($override, $version);

        $result = $this->perform($override, $version);

        ConcreteAgentVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ConcreteAgentOverrideModel $override, int $version): ConcreteAgentOverrideModel
    {
        /** @var ConcreteAgentOverrideVersionModel $overrideVersion */
        $overrideVersion = $override->versions()->where('version', $version)->firstOrFail();

        $override->published_version_id = (string) $overrideVersion->getKey();
        $override->save();

        $this->cache->forget($this->cache->concreteAgentsKey());

        return $override->load('publishedVersion');
    }
}
