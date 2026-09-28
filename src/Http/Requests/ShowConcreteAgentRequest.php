<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\ShowConcreteAgentAction;
use JayI\Cortex\Http\Resources\ConcreteAgentResource;

final class ShowConcreteAgentRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->overrideOrNew());
    }

    public function persist(): JsonResponse
    {
        $agent = app(ShowConcreteAgentAction::class)->execute($this->agentName());

        return (new ConcreteAgentResource($agent))->response();
    }
}
