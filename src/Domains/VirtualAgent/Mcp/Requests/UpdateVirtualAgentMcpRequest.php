<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Illuminate\Support\Arr;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;

final class UpdateVirtualAgentMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->agent());
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            ...UpdateVirtualAgentAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agent = app(UpdateVirtualAgentAction::class)->execute(
            $this->agent(),
            Arr::except($validated, ['slug']),
        );

        return Response::structured((new VirtualAgentResource($agent))->resolve());
    }
}
