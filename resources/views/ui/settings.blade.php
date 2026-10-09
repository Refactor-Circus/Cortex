<div class="flex flex-col gap-4">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-atrium::stat :label="__('cortex::cortex.providers')" :value="count($providers)" />
        <x-atrium::stat :label="__('cortex::cortex.registered_tools')" :value="$toolCount" />
        <x-atrium::stat :label="__('cortex::cortex.concrete_agents')" :value="$agentCount" />
        <x-atrium::stat :label="__('cortex::cortex.mcp_servers')" :value="$serverCount" />
    </div>

    <x-atrium::card :title="__('cortex::cortex.cache')">
        @php($cacheStatus = $cacheEnabled ? 'enabled' : 'disabled')
        <x-atrium::status-dot :variant="\RefactorCircus\Cortex\Atrium\Badges::forStatus($cacheStatus)" :label="__('cortex::cortex.'.$cacheStatus)" data-status="{{ $cacheStatus }}" />
    </x-atrium::card>
</div>
