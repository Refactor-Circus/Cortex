<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Resources\ToolDescriptionResource;

final class PublishToolDescriptionVersionRequest extends ToolDescriptionRequest
{
    public function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    public function persist(): JsonResponse
    {
        $description = app(PublishToolDescriptionVersionAction::class)->execute(
            $this->description(),
            (int) $this->route('version'),
        );

        return (new ToolDescriptionResource($description))->response();
    }
}
