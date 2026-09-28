<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Http\Request;
use JayI\Cortex\Http\Resources\VirtualAgentResource;
use JayI\Cortex\Models\VirtualAgent;

final class IndexVirtualAgentsRequest extends Request
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgent::class);
    }

    public function rules(): array
    {
        return ListVirtualAgentsAction::rules();
    }

    public function persist(): JsonResponse
    {
        $agents = app(ListVirtualAgentsAction::class)->execute();

        return VirtualAgentResource::collection($agents)->response();
    }
}
