<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\Tool\Actions\ListToolDescriptionVersionsAction;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Resources\ToolDescriptionVersionResource;

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
