<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import { cortexData, useCortexLoad, type CortexList, type CortexServer } from '../../components/cortex'
import CortexLoad from '../../components/CortexLoad.vue'
import CortexPage from '../../components/CortexPage.vue'

/**
 * `cortex.servers`: the MCP servers (`servers.index`), with the tools each
 * serves and whether they are overridden, each linking to its instructions
 * and tools.
 */
const { t } = useI18n()

const { data, pending, failed, status } = useCortexLoad(async cortex =>
  cortexData<CortexList<CortexServer>>(await cortex.GET('/servers')).data)

const servers = computed(() => data.value ?? [])

const columns = computed<TableColumn<CortexServer>[]>(() => [
  { accessorKey: 'name', header: t('cortex.agent.name') },
  { id: 'tools', header: t('cortex.agent.tools') },
  { id: 'actions', header: t('cortex.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])
</script>

<template>
  <CortexPage :title="t('cortex.servers.title')" screen-key="cortex.servers">
    <CortexLoad :ready="!pending" :failed="failed" :status="status">
      <UEmpty
        v-if="servers.length === 0"
        icon="i-lucide-server"
        :title="t('cortex.servers.empty')"
        variant="naked"
        role="status"
        data-testid="cortex-empty"
      />

      <UTable v-else :data="servers" :columns="columns" data-testid="cortex-servers">
        <template #name-cell="{ row }">
          <code class="text-xs" :data-testid="`cortex-server-${row.original.name}`">{{ row.original.name }}</code>
        </template>
        <template #tools-cell="{ row }">
          <div class="flex flex-wrap items-center gap-2">
            <span>{{ t('cortex.tools.count', { count: row.original.tools.length }) }}</span>
            <UBadge v-if="row.original.tools_overridden" color="success" variant="subtle" :label="t('cortex.tools.overridden')" />
            <UBadge
              v-if="row.original.missing_tools.length"
              color="warning"
              variant="subtle"
              :label="t('cortex.servers.missing_tools', { tools: row.original.missing_tools.join(', ') })"
              data-testid="cortex-missing-tools"
            />
          </div>
        </template>
        <template #actions-cell="{ row }">
          <UButton
            size="xs"
            variant="outline"
            icon="i-lucide-file-text"
            :to="`/cortex/servers/${row.original.name}/instructions`"
            :label="t('cortex.servers.edit_instructions')"
            :data-testid="`cortex-instructions-${row.original.name}`"
          />
        </template>
      </UTable>
    </CortexLoad>
  </CortexPage>
</template>
