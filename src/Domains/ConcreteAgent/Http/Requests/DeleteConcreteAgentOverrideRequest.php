<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\Response;
use JayI\Cortex\Domains\ConcreteAgent\Actions\DeleteConcreteAgentOverrideAction;

final class DeleteConcreteAgentOverrideRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->override());
    }

    public function persist(): Response
    {
        app(DeleteConcreteAgentOverrideAction::class)->execute($this->override());

        return response()->noContent();
    }
}
