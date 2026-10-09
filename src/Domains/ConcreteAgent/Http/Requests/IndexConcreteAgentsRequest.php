<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Http\Request;

final class IndexConcreteAgentsRequest extends Request
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverrideModel::class);
    }

    public function rules(): array
    {
        return ListConcreteAgentsAction::rules();
    }

    public function persist(): JsonResponse
    {
        $agents = app(ListConcreteAgentsAction::class)->execute();

        return ConcreteAgentResource::collection($agents)->response();
    }
}
