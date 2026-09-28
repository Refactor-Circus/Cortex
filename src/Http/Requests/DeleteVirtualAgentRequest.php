<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\Response;
use JayI\Cortex\Actions\DeleteVirtualAgentAction;

final class DeleteVirtualAgentRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->agent());
    }

    public function persist(): Response
    {
        app(DeleteVirtualAgentAction::class)->execute($this->agent());

        return response()->noContent();
    }
}
