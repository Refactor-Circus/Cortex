<script setup lang="ts">
import type { TableColumn } from '@refactor-circus/atrium/ui'
import { cortexData, useCortexAction, useCortexLoad, type CortexList, type CortexRedirectDomain } from '../components/cortex'
import CortexActionError from '../components/CortexActionError.vue'
import CortexLoad from '../components/CortexLoad.vue'
import CortexPage from '../components/CortexPage.vue'

/**
 * `cortex.redirect-domains`: the stored OAuth redirect domains
 * (`redirect-domains.index`), global and owned, with add
 * (`redirect-domains.store`) and remove (`redirect-domains.destroy`). The
 * v1 source filter, configured-domains block and paging have no v2 route
 * (see `app/integrations/cortex/index.ts`).
 */
const { t } = useI18n()
const action = useCortexAction()
const busy = action.busy

const domain = ref('')

const { client, data, pending, failed, status, reload } = useCortexLoad(async cortex =>
  cortexData<CortexList<CortexRedirectDomain>>(await cortex.GET('/redirect-domains')).data)

const domains = computed(() => data.value ?? [])

const columns = computed<TableColumn<CortexRedirectDomain>[]>(() => [
  { accessorKey: 'domain', header: t('cortex.redirect_domains.domain') },
  { id: 'source', header: t('cortex.redirect_domains.source') },
  { id: 'created', header: t('cortex.redirect_domains.created') },
  { id: 'actions', header: t('cortex.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

async function add(): Promise<void> {
  if (client && await action.run(async () => cortexData(await client.POST('/redirect-domains', { body: { domain: domain.value } })))) {
    domain.value = ''
    await reload()
  }
}

async function remove(entry: CortexRedirectDomain): Promise<void> {
  if (client && await action.run(async () => cortexData(await client.DELETE('/redirect-domains/{domain}', { params: { path: { domain: entry.id } } })))) {
    await reload()
  }
}
</script>

<template>
  <CortexPage :title="t('cortex.redirect_domains.title')" screen-key="cortex.redirect-domains" :description="t('cortex.redirect_domains.description')">
    <CortexLoad :ready="!pending" :failed="failed" :status="status">
      <div class="flex flex-col gap-4">
        <CortexActionError :forbidden="action.forbidden.value" :message="action.message.value" />

        <UCard>
          <template #header>
            <h3 class="font-medium">{{ t('cortex.redirect_domains.add') }}</h3>
          </template>
          <UForm class="flex flex-wrap items-start gap-3" data-testid="cortex-add-domain-form" @submit="add">
            <UFormField :label="t('cortex.redirect_domains.domain')" :hint="t('cortex.redirect_domains.domain_hint')" :error="action.error('domain')" class="w-80" required>
              <UInput v-model="domain" placeholder="https://claude.ai" class="w-full" data-testid="cortex-domain" />
            </UFormField>
            <UButton type="submit" icon="i-lucide-plus" :label="t('cortex.redirect_domains.add')" :loading="busy" class="mt-field-label" data-testid="cortex-add-domain" />
          </UForm>
        </UCard>

        <UEmpty
          v-if="domains.length === 0"
          icon="i-lucide-globe"
          :title="t('cortex.redirect_domains.empty')"
          variant="naked"
          role="status"
          data-testid="cortex-empty"
        />

        <UTable v-else :data="domains" :columns="columns" data-testid="cortex-redirect-domains">
          <template #domain-cell="{ row }">
            <code class="text-xs" :data-testid="`cortex-domain-${row.original.id}`">{{ row.original.domain }}</code>
          </template>
          <template #source-cell="{ row }">
            <span v-if="row.original.owner_type">{{ row.original.owner_id }} <span class="text-xs text-muted">{{ row.original.owner_type }}</span></span>
            <UBadge v-else color="neutral" variant="subtle" :label="t('cortex.redirect_domains.global')" />
          </template>
          <template #created-cell="{ row }">
            {{ row.original.created_at ?? '—' }}
          </template>
          <template #actions-cell="{ row }">
            <UButton
              size="xs"
              color="neutral"
              variant="ghost"
              icon="i-lucide-trash"
              :label="t('cortex.redirect_domains.remove')"
              :data-testid="`cortex-remove-domain-${row.original.id}`"
              @click="remove(row.original)"
            />
          </template>
        </UTable>
      </div>
    </CortexLoad>
  </CortexPage>
</template>
