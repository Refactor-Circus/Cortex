<?php

declare(strict_types=1);

namespace JayI\Cortex\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use JayI\Foundation\Http\Requests\Request as FoundationRequest;

/**
 * Base HTTP request.
 *
 * Validation rules come from the Action the request wraps, and `persist()`
 * calls that same Action. Each request's `authorize()` checks the model it
 * touches against the policies in `cortex.policies`.
 *
 * Unlike the shared runtime's `authorization` switch, Cortex always asks the
 * Gate, as the authenticated user or as a guest: its bundled policies allow
 * guests, so the route middleware stays the gate until an application
 * registers stricter ones.
 */
abstract class Request extends FoundationRequest
{
    public function authorize(): bool
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
