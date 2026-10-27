<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ClearConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;

final class ClearServerToolsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('updateTools', $this->instructionOrNew());
    }

    protected function rules(): array
    {
        return $this->serverRules() + ClearConcreteServerToolsData::rules($this->serverName());
    }

    protected function handle(array $validated): Response
    {
        app(ClearConcreteServerToolsAction::class)->execute(
            $this->serverName(),
            ClearConcreteServerToolsData::fromValidated($validated),
        );

        return Response::text('Server tool override cleared. The server serves its code-declared tools.');
    }
}
