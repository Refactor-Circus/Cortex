<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use JayI\Cortex\Http\Request;

final class IndexVirtualAgentsRequest extends Request
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class);
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
