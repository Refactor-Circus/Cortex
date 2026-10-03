<x-atrium::layout :title="__('cortex::cortex.server_instructions')">
    <x-atrium::page-header :title="$server" :description="__('cortex::cortex.server_instructions')">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="__('cortex::cortex.back_to_servers')" variant="ghost"
                                   :href="route('atrium.cortex.servers.index')" data-testid="back" />
        </x-slot:actions>
    </x-atrium::page-header>

    @include('cortex::ui.partials.override', [
        'override' => $instruction,
        'subject' => $subject,
        'versionClass' => \JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel::class,
        'fallback' => $codeInstructions,
        'versions' => $versions,
        'storeRoute' => route('atrium.cortex.servers.instructions.store', $server),
        'destroyRoute' => route('atrium.cortex.servers.instructions.destroy', $server),
        'publishRoute' => fn (int $version) => route('atrium.cortex.servers.instructions.publish', [$server, $version]),
    ])
</x-atrium::layout>
