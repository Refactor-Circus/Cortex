<script setup lang="ts">
import { cortexData, useCortexAction, useCortexLoad, type CortexList, type CortexRedirectDomain } from '../cortex'
import CortexActionError from '../CortexActionError.vue'
import { ownerOf } from './hooks'

/**
 * The `cortex.mcp` tab and the `cortex.redirect-domains` section (AC 65): the
 * OAuth redirect domains the screen's record owns. Atrium sends the record's
 * morph alias as `owner_type`; the record key is the owner id. It lists
 * `cortex.redirect-domains.index?owner_type=&owner_id=`, adds a domain for
 * that owner (`store`) and removes one (`destroy`), reloading after each.
 *
 * It renders as a titled card, like the other modules' sections on the same
 * screens.
 *
 * Without a record (or an `owner_type`) it renders nothing; a 403 shows the
 * no-access state, a 422 the field errors.
 */
const props = defineProps<{ data: Record<string, unknown>, record?: unknown }>()

const { t } = useI18n()
const action = useCortexAction()
const busy = action.busy

const owner = computed(() => ownerOf(props.data, props.record))
const domain = ref('')

const { client, data: loaded, pending, failed, status, reload } = useCortexLoad(async (cortex) => {
  if (!owner.value) {
    return []
  }

  return cortexData<CortexList<CortexRedirectDomain>>(await cortex.GET('/redirect-domains', {
    params: { query: { owner_type: owner.value.type, owner_id: owner.value.id } }
  })).data
})

const domains = computed(() => loaded.value ?? [])

async function add(): Promise<void> {
  const target = owner.value

  if (client && target && await action.run(async () => cortexData(await client.POST('/redirect-domains', {
    body: { domain: domain.value, owner_type: target.type, owner_id: target.id }
  })))) {
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
  <UCard v-if="owner && client" :ui="{ body: 'space-y-3' }" data-testid="hooks-redirect-domains">
    <template #header>
      <div class="min-w-0">
        <h3 class="font-medium" data-testid="hooks-redirect-domains-title">{{ t('hooks.redirect_domains.title') }}</h3>
        <p class="mt-1 text-sm text-muted" data-testid="hooks-redirect-domains-description">{{ t('hooks.redirect_domains.description') }}</p>
      </div>
    </template>

    <AtriumNoAccess v-if="status === 403" compact />
    <UAlert v-else-if="failed" color="error" variant="subtle" :title="t('hooks.redirect_domains.load_failed')" data-testid="hooks-redirect-domains-failed" />
    <USkeleton v-else-if="pending" class="h-16" />
    <template v-else>
      <CortexActionError :forbidden="action.forbidden.value" :message="action.message.value" />

      <UEmpty
        v-if="domains.length === 0"
        icon="i-lucide-globe"
        :title="t('hooks.redirect_domains.empty')"
        size="xs"
        variant="naked"
        :ui="{ root: 'py-6' }"
        role="status"
        data-testid="hooks-redirect-domains-empty"
      />
      <ul v-else class="divide-y divide-default text-sm">
        <li v-for="entry in domains" :key="entry.id" class="flex items-center justify-between gap-2 py-2" :data-testid="`hooks-redirect-domain-${entry.id}`">
          <code class="truncate text-xs">{{ entry.domain }}</code>
          <UButton
            size="xs"
            color="neutral"
            variant="ghost"
            icon="i-lucide-trash"
            :label="t('hooks.redirect_domains.remove')"
            :loading="busy"
            :data-testid="`hooks-remove-redirect-domain-${entry.id}`"
            @click="remove(entry)"
          />
        </li>
      </ul>

      <UForm class="flex flex-wrap items-start gap-2" data-testid="hooks-add-redirect-domain-form" @submit="add">
        <UFormField :label="t('hooks.redirect_domains.domain')" :error="action.error('domain')" class="min-w-48 flex-1" required>
          <UInput v-model="domain" placeholder="https://claude.ai" class="w-full" data-testid="hooks-redirect-domain" />
        </UFormField>
        <UButton type="submit" icon="i-lucide-plus" :label="t('hooks.redirect_domains.add')" :loading="busy" class="mt-field-label" data-testid="hooks-add-redirect-domain" />
      </UForm>
    </template>
  </UCard>
</template>
