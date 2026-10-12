<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionDeletedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionDeletingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Support\PublicationCache;

final class DeleteToolDescriptionAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    public function execute(ToolDescriptionModel $description): void
    {
        ToolDescriptionDeletingActionEvent::dispatch($description);

        $this->perform($description);

        ToolDescriptionDeletedActionEvent::dispatch($description);
    }

    private function perform(ToolDescriptionModel $description): void
    {
        DB::transaction(function () use ($description): void {
            $description->delete();

            DB::afterCommit(fn () => $this->cache->forget($this->cache->toolDescriptionsKey()));
        });
    }
}
