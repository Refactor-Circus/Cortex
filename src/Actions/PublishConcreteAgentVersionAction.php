<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use JayI\Cortex\Events\Action\ConcreteAgentVersionPublishedActionEvent;
use JayI\Cortex\Events\Action\ConcreteAgentVersionPublishingActionEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;
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

    public function execute(ConcreteAgentOverride $override, int $version): ConcreteAgentOverride
    {
        ConcreteAgentVersionPublishingActionEvent::dispatch($override, $version);

        $result = $this->perform($override, $version);

        ConcreteAgentVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ConcreteAgentOverride $override, int $version): ConcreteAgentOverride
    {
        /** @var ConcreteAgentOverrideVersion $overrideVersion */
        $overrideVersion = $override->versions()->where('version', $version)->firstOrFail();

        $override->published_version_id = (string) $overrideVersion->getKey();
        $override->save();

        $this->cache->forget($this->cache->concreteAgentsKey());

        return $override->load('publishedVersion');
    }
}
