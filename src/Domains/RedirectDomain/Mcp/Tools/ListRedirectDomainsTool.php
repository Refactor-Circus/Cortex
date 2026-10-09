<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Cortex\Domains\RedirectDomain\Mcp\Requests\ListRedirectDomainsMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the stored origins MCP clients may register OAuth redirect URIs on, beside the configured mcp.redirect_domains. Filter to one owner with owner_type and owner_id.')]
final class ListRedirectDomainsTool extends Tool
{
    public function handle(ListRedirectDomainsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'owner_type' => $schema->string()->description('The owner\'s morph alias or model class, such as an organization or user. Omit for every owner.'),
            'owner_id' => $schema->string()->description('The owner\'s key. Required with owner_type.'),
        ];
    }
}
