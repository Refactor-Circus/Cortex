<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ClearConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;

final class ClearConcreteServerToolsRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('updateTools', $this->instructionOrNew());
    }

    public function rules(): array
    {
        return ClearConcreteServerToolsData::rules($this->serverName());
    }

    public function persist(): Response
    {
        app(ClearConcreteServerToolsAction::class)->execute(
            $this->serverName(),
            ClearConcreteServerToolsData::fromValidated($this->validated()),
        );

        return response()->noContent();
    }
}
