<x-atrium::layout :title="__('cortex::cortex.tool_description')">
    <x-atrium::page-header :title="$tool" :description="__('cortex::cortex.tool_description')">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="__('cortex::cortex.back_to_tools')" variant="ghost"
                                   :href="route('atrium.cortex.tools.index')" data-testid="back" />
        </x-slot:actions>
    </x-atrium::page-header>

    @include('cortex::ui.partials.override', [
        'override' => $description,
        'subject' => $subject,
        'versionClass' => \RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel::class,
        'fallback' => $codeDescription,
        'versions' => $versions,
        'storeRoute' => route('atrium.cortex.tools.description.store', $tool),
        'destroyRoute' => route('atrium.cortex.tools.description.destroy', $tool),
        'publishRoute' => fn (int $version) => route('atrium.cortex.tools.description.publish', [$tool, $version]),
    ])
</x-atrium::layout>
