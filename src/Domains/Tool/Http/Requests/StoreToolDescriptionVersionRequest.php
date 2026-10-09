<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Resources\ToolDescriptionVersionResource;

final class StoreToolDescriptionVersionRequest extends ToolDescriptionRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', ToolDescriptionVersionModel::class, [$this->descriptionOrNew()]);
    }

    public function rules(): array
    {
        return CreateToolDescriptionVersionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $version = app(CreateToolDescriptionVersionAction::class)->execute($this->tool(), $this->validated());

        return (new ToolDescriptionVersionResource($version))->response()->setStatusCode(201);
    }
}
