<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\RedirectDomain\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JayI\Cortex\Domains\RedirectDomain\Mcp\Requests\CreateRedirectDomainMcpRequest;
use JayI\Foundation\Mcp\Tool;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Allow MCP clients to register OAuth redirect URIs on an origin. A bare host is taken as https, and a full URL keeps only its origin. Give owner_type and owner_id to file it under an organization or user.')]
final class CreateRedirectDomainTool extends Tool
{
    public function handle(CreateRedirectDomainMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'domain' => $schema->string()->description('The origin, host or redirect URL, such as https://claude.ai.')->required(),
            'owner_type' => $schema->string()->description('The owner\'s morph alias or model class, such as an organization or user. Omit for every owner.'),
            'owner_id' => $schema->string()->description('The owner\'s key. Required with owner_type.'),
        ];
    }
}
