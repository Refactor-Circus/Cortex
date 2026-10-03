<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Actions;

use Illuminate\Database\Eloquent\Collection;
use JayI\Cortex\Domains\Tool\Events\ToolDescriptionVersionsListedActionEvent;
use JayI\Cortex\Domains\Tool\Events\ToolDescriptionVersionsListingActionEvent;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;

final class ListToolDescriptionVersionsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * @return Collection<int, ToolDescriptionVersionModel>
     */
    public function execute(ToolDescriptionModel $description): Collection
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
