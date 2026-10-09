<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;

final class PublishConcreteAgentVersionRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    public function persist(): JsonResponse
    {
        $override = app(PublishConcreteAgentVersionAction::class)->execute(
            $this->override(),
            (int) $this->route('version'),
        );

        return (new ConcreteAgentOverrideResource($override))->response();
    }
}
