<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use Illuminate\Database\Eloquent\Collection;
use JayI\Cortex\Events\Action\ConcreteAgentVersionsListedActionEvent;
use JayI\Cortex\Events\Action\ConcreteAgentVersionsListingActionEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

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
     * @return Collection<int, ConcreteAgentOverrideVersion>
     */
    public function execute(ConcreteAgentOverride $override): Collection
    {
        ConcreteAgentVersionsListingActionEvent::dispatch($override);

        $result = $this->perform($override);

        ConcreteAgentVersionsListedActionEvent::dispatch($override, $result);

        return $result;
    }

    /**
     * @return Collection<int, ConcreteAgentOverrideVersion>
     */
    private function perform(ConcreteAgentOverride $override): Collection
    {
        return $override->versions()->orderByDesc('version')->get();
    }
}
