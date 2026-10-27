<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionPublishedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionPublishingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Keystone\Actions\Action;

final class PublishToolDescriptionVersionAction extends Action
{
    public function __construct(private readonly PublicationCache $cache) {}

    protected function handle(ToolDescriptionModel $description, int $version): ToolDescriptionModel
    {
        ToolDescriptionVersionPublishingActionEvent::dispatch($description, $version);

        $result = $this->perform($description, $version);

        ToolDescriptionVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ToolDescriptionModel $description, int $version): ToolDescriptionModel
    {
        return DB::transaction(function () use ($description, $version): ToolDescriptionModel {
            /** @var ToolDescriptionVersionModel $descriptionVersion */
            $descriptionVersion = $description->versions()->where('version', $version)->firstOrFail();

            $description->published_version_id = $descriptionVersion->getKey();
            $description->save();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->toolDescriptionsKey()));

            return $description->load('publishedVersion');
        });
    }
}
