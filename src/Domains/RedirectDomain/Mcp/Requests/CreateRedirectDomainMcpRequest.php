<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Mcp\Requests;

use Illuminate\Support\Arr;
use JayI\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use JayI\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use JayI\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use JayI\Cortex\Domains\RedirectDomain\Resources\RedirectDomainResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateRedirectDomainMcpRequest extends RedirectDomainMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', RedirectDomainModel::class, [$this->owner()]);
    }

    protected function rules(): array
    {
        return [
            ...CreateRedirectDomainAction::rules(),
            ...ListRedirectDomainsAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $domain = app(CreateRedirectDomainAction::class)->execute(
            Arr::except($validated, ['owner_type', 'owner_id']),
            $this->owner(),
        );

        return Response::structured((new RedirectDomainResource($domain))->resolve());
    }
}
