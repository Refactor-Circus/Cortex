<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionPublishedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionPublishingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class PublishToolDescriptionVersionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(ToolDescriptionModel $description, int $version): ToolDescriptionModel
    {
        ToolDescriptionVersionPublishingActionEvent::dispatch($description, $version);

        $result = $this->perform($description, $version);

        ToolDescriptionVersionPublishedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ToolDescriptionModel $description, int $version): ToolDescriptionModel
    {
        /** @var ToolDescriptionVersionModel $descriptionVersion */
        $descriptionVersion = $description->versions()->where('version', $version)->firstOrFail();

        $description->published_version_id = $descriptionVersion->getKey();
        $description->save();

        $this->cache->forget($this->cache->toolDescriptionsKey());

        return $description->load('publishedVersion');
    }
}
