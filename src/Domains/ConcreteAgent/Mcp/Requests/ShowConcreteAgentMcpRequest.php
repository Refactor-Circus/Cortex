<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use JayI\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction;
use JayI\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowConcreteAgentMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->overrideOrNew());
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agent = app(ShowConcreteAgentAction::class)->execute($this->agentName());

        return Response::structured((new ConcreteAgentResource($agent))->resolve());
    }
}
