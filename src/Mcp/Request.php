<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use JayI\Foundation\Mcp\Requests\Request as FoundationRequest;

/**
 * Base MCP request: mirrors the HTTP FormRequest `persist()` pattern so
 * tools stay thin and both surfaces resolve the same Actions.
 *
 * Unlike the shared runtime's `authorization` switch, Cortex always asks the
 * Gate, as the authenticated user or as a guest: its bundled policies allow
 * guests, so the route middleware stays the gate until an application
 * registers stricter ones.
 */
abstract class Request extends FoundationRequest
{
    /**
     * Authorize the tool call. Requests that touch a model override this to
     * check it against the policies in `cortex.policies`.
     */
    protected function authorize(): bool
    {
        return true;
    }

    /**
     * Check an ability against the model's policy from `cortex.policies`, as
     * the authenticated user or as a guest.
     *
     * @param  Model|class-string<Model>  $subject
     * @param  array<int, mixed>  $arguments
     */
    protected function allows(string $ability, Model|string $subject, array $arguments = []): bool
    {
        return Gate::forUser($this->user())->allows($ability, [$subject, ...$arguments]);
    }
}
