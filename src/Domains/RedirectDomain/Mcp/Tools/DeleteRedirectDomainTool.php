<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests\DeleteRedirectDomainMcpRequest;
use RefactorCircus\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Remove a stored redirect domain. Clients already registered on it keep their registration.')]
final class DeleteRedirectDomainTool extends Tool
{
    public function handle(DeleteRedirectDomainMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The redirect domain id.')->required(),
        ];
    }
}
