<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\Tool\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\Tool\Actions\ListToolsAction;
use JayI\Cortex\Domains\Tool\Http\Resources\ToolResource;
use JayI\Cortex\Http\Request;

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
