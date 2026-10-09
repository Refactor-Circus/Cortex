<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\Tool\Actions\ListToolsAction;
use RefactorCircus\Cortex\Domains\Tool\Http\Resources\ToolResource;
use RefactorCircus\Cortex\Http\Request;

final class IndexToolsRequest extends Request
{
    public function rules(): array
    {
        return ListToolsAction::rules();
    }

    public function persist(): JsonResponse
    {
        /** @var string|null $tag */
        $tag = $this->validated('tag');

        $tools = app(ListToolsAction::class)->execute($tag);

        return ToolResource::collection($tools)->response();
    }
}
