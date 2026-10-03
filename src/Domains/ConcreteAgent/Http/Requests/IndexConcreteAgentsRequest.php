<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Http\Request;

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
