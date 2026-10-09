<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Cortex\Atrium\ScreenAccess;

/**
 * The same policy checks the JSON API and MCP tools make, for Atrium screens.
 */
trait AuthorizesScreens
{
    /**
     * @param  Model|class-string<Model>  $subject
     * @param  array<int, mixed>  $arguments
     */
    private function authorizeScreen(string $ability, Model|string $subject, array $arguments = []): void
    {
        abort_unless(ScreenAccess::allows($ability, $subject, $arguments), 403);
    }
}
