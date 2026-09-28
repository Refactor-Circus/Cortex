<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Http\Request;
use JayI\Cortex\Http\Resources\ConcreteAgentResource;
use JayI\Cortex\Models\ConcreteAgentOverride;

final class IndexConcreteAgentsRequest extends Request
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverride::class);
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
