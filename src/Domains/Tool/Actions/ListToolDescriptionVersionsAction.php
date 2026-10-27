<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionsListedActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Events\ToolDescriptionVersionsListingActionEvent;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListToolDescriptionVersionsAction extends Action
{
    /**
     * @return Collection<int, ToolDescriptionVersionModel>
     */
    protected function handle(ToolDescriptionModel $description): Collection
    {
        ToolDescriptionVersionsListingActionEvent::dispatch($description);

        $result = $this->perform($description);

        ToolDescriptionVersionsListedActionEvent::dispatch($description, $result);

        return $result;
    }

    /**
     * @return Collection<int, ToolDescriptionVersionModel>
     */
    private function perform(ToolDescriptionModel $description): Collection
    {
        return $description->versions()->orderByDesc('version')->get();
    }
}
