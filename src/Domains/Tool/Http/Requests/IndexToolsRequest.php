<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\Tool\Actions\ListToolsAction;
use RefactorCircus\Cortex\Domains\Tool\Data\ListToolsData;
use RefactorCircus\Cortex\Domains\Tool\Http\Resources\ToolResource;
use RefactorCircus\Cortex\Http\Request;

final class IndexToolsRequest extends Request
{
    public function rules(): array
    {
        return ListToolsData::rules();
    }

    public function persist(): JsonResponse
    {
        $tools = app(ListToolsAction::class)->execute(ListToolsData::fromValidated($this->validated()));

        return ToolResource::collection($tools)->response();
    }
}
