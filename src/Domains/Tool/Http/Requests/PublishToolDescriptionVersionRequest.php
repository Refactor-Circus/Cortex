<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction;
use JayI\Cortex\Domains\Tool\Resources\ToolDescriptionResource;

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
