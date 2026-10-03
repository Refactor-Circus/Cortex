<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\Tool\Actions\ShowToolDescriptionAction;
use JayI\Cortex\Domains\Tool\Resources\ToolDescriptionResource;

final class ShowToolDescriptionRequest extends ToolDescriptionRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->description());
    }

    public function persist(): JsonResponse
    {
        $description = app(ShowToolDescriptionAction::class)->execute($this->tool());

        return (new ToolDescriptionResource($description))->response();
    }
}
