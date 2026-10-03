<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use JayI\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;

final class UpdateConcreteAgentToolsRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->overrideOrNew());
    }

    public function rules(): array
    {
        return UpdateConcreteAgentToolsAction::rules($this->agentName());
    }

    public function persist(): JsonResponse
    {
        /** @var list<string>|null $tools */
        $tools = $this->validated('tools');

        $override = app(UpdateConcreteAgentToolsAction::class)->execute($this->agentName(), $tools);

        // PUT sets state, so it answers 200 even when it first creates the row.
        return (new ConcreteAgentOverrideResource($override))->response()->setStatusCode(200);
    }
}
