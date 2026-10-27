<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionPublishedActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionPublishingActionEvent;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;

final class PublishConcreteAgentVersionAction extends Action
{
    public function __construct(private readonly PublicationCache $cache) {}

    protected function handle(ConcreteAgentOverrideModel $override, int $version): ConcreteAgentOverrideModel
    {
        ConcreteAgentVersionPublishingActionEvent::dispatch($override, $version);

        $result = $this->perform($override, $version);

        ConcreteAgentVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ConcreteAgentOverrideModel $override, int $version): ConcreteAgentOverrideModel
    {
        return DB::transaction(function () use ($override, $version): ConcreteAgentOverrideModel {
            /** @var ConcreteAgentOverrideVersionModel $overrideVersion */
            $overrideVersion = $override->versions()->where('version', $version)->firstOrFail();

            $override->published_version_id = (string) $overrideVersion->getKey();
            $override->save();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->concreteAgentsKey()));

            return $override->load('publishedVersion');
        });
    }
}
