<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Actions;

use Illuminate\Database\Eloquent\Collection;
use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionsListedActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentVersionsListingActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;

final class ListConcreteAgentVersionsAction
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * @return Collection<int, ConcreteAgentOverrideVersionModel>
     */
    public function execute(ConcreteAgentOverrideModel $override): Collection
    {
        ConcreteAgentVersionsListingActionEvent::dispatch($override);

        $result = $this->perform($override);

        ConcreteAgentVersionsListedActionEvent::dispatch($override, $result);

        return $result;
    }

    /**
     * @return Collection<int, ConcreteAgentOverrideVersionModel>
     */
    private function perform(ConcreteAgentOverrideModel $override): Collection
    {
        return $override->versions()->orderByDesc('version')->get();
    }
}
