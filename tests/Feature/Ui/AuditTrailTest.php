<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Foundation\Audit\Contracts\AuditTrail;
use JayI\Foundation\Audit\Data\AuditEntry;
use JayI\Foundation\Audit\Data\AuditFilter;
use JayI\Foundation\Audit\Data\AuditPage;

beforeEach(function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    config()->set('cortex.tools', ['echo' => EchoTool::class]);
});

function fakeCortexAuditTrail(): void
{
    app()->instance(AuditTrail::class, new class implements AuditTrail
    {
        public function available(): bool
        {
            return true;
        }

        public function entries(AuditFilter $filter): AuditPage
        {
            return new AuditPage([
                new AuditEntry(1, 'cortex', 'cortex.virtual_agent.updated', 'atrium', CarbonImmutable::now(), actorLabel: 'Ada'),
            ]);
        }
    });
}

it('renders no history until an audit log is installed', function (): void {
    $agent = VirtualAgentModel::factory()->published()->create(['slug' => 'helper']);

    $this->get(route('atrium.cortex.virtual-agents.edit', $agent->slug))
        ->assertOk()
        ->assertDontSee('data-testid="audit-trail"', false);

    $this->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertDontSee('data-testid="audit-trail"', false);
});

it('shows a virtual agent its own history', function (): void {
    fakeCortexAuditTrail();

    $agent = VirtualAgentModel::factory()->published()->create(['slug' => 'helper']);

    $this->get(route('atrium.cortex.virtual-agents.edit', $agent->slug))
        ->assertOk()
        ->assertSee('data-testid="audit-trail"', false)
        ->assertSee('cortex.virtual_agent.updated')
        ->assertSee('Ada');
});

it('shows an override its own history', function (): void {
    fakeCortexAuditTrail();

    ToolDescriptionModel::query()->create(['tool' => 'echo']);

    $this->get(route('atrium.cortex.tools.description', 'echo'))
        ->assertOk()
        ->assertSee('data-testid="audit-trail"', false)
        ->assertSee('cortex.virtual_agent.updated');
});

it('shows the whole of cortex history on the virtual agents index', function (): void {
    fakeCortexAuditTrail();

    $this->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee('data-testid="audit-trail"', false);
});
