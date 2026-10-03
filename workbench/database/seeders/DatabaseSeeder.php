<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use JayI\Cortex\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Actions\CreateToolDescriptionVersionAction;
use JayI\Cortex\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Actions\UpdateConcreteAgentToolsAction;
use Workbench\Database\Factories\UserFactory;

/**
 * Demo data, built through Cortex's own Actions, so every screen has
 * something in each state: published, draft, overridden, from code, locked.
 * The tools, agents and servers it refers to are registered by the
 * WorkbenchServiceProvider.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Signed in by `workbench.user` in testbench.yaml.
        UserFactory::new()->create(['name' => 'Admin', 'email' => 'admin@example.com']);

        $this->virtualAgents();
        $this->concreteAgents();
        $this->toolDescriptions();
        $this->serverInstructions();
    }

    private function virtualAgents(): void
    {
        $create = app(CreateVirtualAgentAction::class);
        $version = app(CreateVirtualAgentVersionAction::class);

        // Published v2, with a v3 draft waiting for review.
        $concierge = $create->execute([
            'name' => 'Order Concierge',
            'slug' => 'order-concierge',
            'description' => 'Answers where-is-my-order questions and hands refunds to the refund agent.',
            'instructions' => 'You help customers with their orders. Look the order up before answering.',
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-5',
            'settings' => ['temperature' => 0.3, 'max_steps' => 6],
            'tools' => ['lookup-order', 'check-inventory', 'current-time'],
            'concrete_sub_agents' => ['refund-agent'],
        ]);
        $version->execute($concierge, [
            'content' => "You help customers with their orders.\n\n- Always look the order up before answering.\n- Quote the carrier and tracking status.\n- Hand refund requests to the refund agent.",
            'publish' => true,
        ]);
        $version->execute($concierge, [
            'content' => "You help customers with their orders.\n\n- Always look the order up before answering.\n- Quote the carrier and tracking status.\n- Check stock before promising a replacement.\n- Hand refund requests to the refund agent.",
        ]);

        // Orchestrates a virtual and a concrete sub-agent.
        $desk = $create->execute([
            'name' => 'Support Desk',
            'slug' => 'support-desk',
            'description' => 'Front door for customer support: routes to the right specialist.',
            'instructions' => 'You are the first point of contact for customer support. Route order questions to the order concierge.',
            'provider' => 'anthropic',
            'model' => 'claude-opus-4-8',
            'settings' => ['temperature' => 0.2, 'max_steps' => 10],
            'tools' => ['search-knowledge-base', 'create-ticket'],
            'sub_agents' => ['order-concierge'],
            'concrete_sub_agents' => ['support-triage'],
        ]);
        $version->execute($desk, [
            'content' => "You are the first point of contact for customer support.\n\nRoute order questions to the order concierge and anything else to support triage. Be warm and brief.",
            'publish' => true,
        ]);

        // v3 went out, then was rolled back to v2.
        $notes = $create->execute([
            'name' => 'Release Notes Writer',
            'slug' => 'release-notes-writer',
            'description' => 'Turns merged pull requests into customer-facing release notes.',
            'instructions' => 'Write release notes from the given list of changes.',
            'provider' => 'openai',
            'model' => 'gpt-5-mini',
            'settings' => ['temperature' => 0.7, 'max_tokens' => 2000],
        ]);
        $version->execute($notes, [
            'content' => "Write release notes from the given list of changes.\n\nGroup them under Added, Changed and Fixed. Leave out internal refactors.",
            'publish' => true,
        ]);
        $version->execute($notes, [
            'content' => "Write release notes from the given list of changes, in a playful tone with emoji.\n\nGroup them under Added, Changed and Fixed.",
            'publish' => true,
        ]);
        app(PublishVirtualAgentVersionAction::class)->execute($notes, 2);

        // A single version on the default provider and model.
        $create->execute([
            'name' => 'Onboarding Guide',
            'slug' => 'onboarding-guide',
            'description' => 'Walks new customers through setting up their account.',
            'instructions' => 'Guide a new customer through account setup one step at a time. Ask which step they are on before explaining it.',
        ]);

        // Uses a plain concrete agent as a sub-agent, with a draft pending.
        $incidents = $create->execute([
            'name' => 'Incident Summarizer',
            'slug' => 'incident-summarizer',
            'description' => 'Summarizes incident timelines for the status page.',
            'instructions' => 'Summarize the incident timeline for customers. No internal hostnames or names.',
            'provider' => 'anthropic',
            'model' => 'claude-haiku-4-5',
            'tools' => ['current-time'],
            'concrete_sub_agents' => ['summarizer'],
        ]);
        $version->execute($incidents, [
            'content' => "Summarize the incident timeline for customers.\n\nState impact, start and end times in UTC, and the fix. No internal hostnames or names.",
        ]);
    }

    private function concreteAgents(): void
    {
        $version = app(CreateConcreteAgentVersionAction::class);

        // Prompt overridden at v2 with a v3 draft, toolset overridden too.
        $version->execute('support-triage', [
            'content' => 'You triage incoming support requests. Search the knowledge base first; open a ticket only when no article answers the question.',
            'publish' => true,
        ]);
        $version->execute('support-triage', [
            'content' => "You triage incoming support requests.\n\n1. Search the knowledge base.\n2. For order questions, look the order up.\n3. Open a ticket only when nothing answers the question.",
            'publish' => true,
        ]);
        $version->execute('support-triage', [
            'content' => "You triage incoming support requests.\n\n1. Search the knowledge base.\n2. For order questions, look the order up.\n3. Open a ticket only when nothing answers the question, tagged with its urgency.",
        ]);
        app(UpdateConcreteAgentToolsAction::class)->execute('support-triage', ['search-knowledge-base', 'lookup-order', 'create-ticket']);

        // Prompt overridden; its toolset is locked in code.
        $version->execute('refund-agent', [
            'content' => 'You process refund requests. Look the order up first. Refund delivered orders under 60 days old; escalate anything else.',
            'publish' => true,
        ]);

        // Prompt from code, toolset overridden.
        app(UpdateConcreteAgentToolsAction::class)->execute('catalog-agent', ['check-inventory', 'current-time']);

        // summarizer is a plain laravel/ai agent: everything from code.
    }

    private function toolDescriptions(): void
    {
        $version = app(CreateToolDescriptionVersionAction::class);

        // Published v1 with a v2 draft.
        $version->execute('lookup-order', [
            'content' => 'Look up an order by its number (for example A-10042) and return its status, carrier, tracking and items.',
            'publish' => true,
        ]);
        $version->execute('lookup-order', [
            'content' => 'Look up an order by its number (for example A-10042) and return its status, carrier, tracking and items. Use it before answering any question about an order.',
        ]);

        // Published v2.
        $version->execute('refund-order', [
            'content' => 'Refund an order to its original payment method.',
            'publish' => true,
        ]);
        $version->execute('refund-order', [
            'content' => 'Refund an order in full to its original payment method. Irreversible: confirm the order number with the customer first.',
            'publish' => true,
        ]);

        // A draft only: the description from code is still live.
        $version->execute('current-time', [
            'content' => 'Tell the current date and time in a timezone. Use it to work out delivery windows and business hours.',
        ]);
    }

    private function serverInstructions(): void
    {
        $version = app(CreateMcpInstructionVersionAction::class);

        // Published v2 with a v3 draft.
        $version->execute('support', [
            'content' => 'Help customers with orders and support questions. Search the knowledge base before opening a ticket.',
            'publish' => true,
        ]);
        $version->execute('support', [
            'content' => "Help customers with orders and support questions.\n\nSearch the knowledge base before opening a ticket, and look orders up rather than guessing their status.",
            'publish' => true,
        ]);
        $version->execute('support', [
            'content' => "Help customers with orders and support questions.\n\nSearch the knowledge base before opening a ticket, look orders up rather than guessing, and never promise refunds.",
        ]);

        // A draft only for Cortex's own server; catalog stays from code.
        $version->execute('cortex', [
            'content' => 'Manage Cortex virtual agents, concrete agents, tools and MCP server instructions. Prefer publishing a new version over editing an agent in place.',
        ]);
    }
}
