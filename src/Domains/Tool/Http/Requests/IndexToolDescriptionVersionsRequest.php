<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\Tool\Actions\ListToolDescriptionVersionsAction;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use JayI\Cortex\Domains\Tool\Resources\ToolDescriptionVersionResource;

final class IndexToolDescriptionVersionsRequest extends ToolDescriptionRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', ToolDescriptionVersionModel::class, [$this->description()]);
    }

    public function persist(): JsonResponse
    {
        $versions = app(ListToolDescriptionVersionsAction::class)->execute($this->description());

        return ToolDescriptionVersionResource::collection($versions)->response();
    }
}
