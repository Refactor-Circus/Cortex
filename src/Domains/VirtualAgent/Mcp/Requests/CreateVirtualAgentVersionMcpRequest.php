<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Illuminate\Support\Arr;
use JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateVirtualAgentVersionMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', VirtualAgentVersionModel::class, [$this->agent()]);
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            ...CreateVirtualAgentVersionAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(CreateVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            Arr::except($validated, ['slug']),
        );

        return Response::structured((new VirtualAgentVersionResource($version))->resolve());
    }
}
