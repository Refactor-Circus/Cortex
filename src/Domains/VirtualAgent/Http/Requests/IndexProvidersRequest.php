<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListProvidersAction;
use RefactorCircus\Cortex\Http\Request;

final class IndexProvidersRequest extends Request
{
    public function persist(): JsonResponse
    {
        return new JsonResponse([
            'data' => app(ListProvidersAction::class)->execute(),
        ]);
    }
}
