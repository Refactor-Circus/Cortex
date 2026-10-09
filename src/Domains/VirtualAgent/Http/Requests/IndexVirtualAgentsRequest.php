<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use RefactorCircus\Cortex\Http\Request;

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
