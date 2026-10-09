<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionDeletedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionDeletingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteToolDescriptionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    public function execute(ToolDescriptionModel $description): void
    {
        ToolDescriptionDeletingActionEvent::dispatch($description);

        $this->perform($description);

        ToolDescriptionDeletedActionEvent::dispatch($description);
    }

    private function perform(ToolDescriptionModel $description): void
    {
        $description->delete();

        $this->cache->forget($this->cache->toolDescriptionsKey());
    }
}
