<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Http\Requests;

use Illuminate\Http\Response;
use JayI\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction;

final class DeleteMcpInstructionRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->instruction());
    }

    public function persist(): Response
    {
        app(DeleteMcpInstructionAction::class)->execute($this->instruction());

        return response()->noContent();
    }
}
