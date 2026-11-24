import { a as e, i as t, t as n } from "./chunks/cortex-Bp7gHDgF.js";
import { t as r } from "./chunks/CortexLoad-Bz-c2t3K.js";
import { t as i } from "./chunks/CortexActionError-BnFnixGr.js";
import { AtriumNoAccess as a, defineAtriumModule as o, integrationData as s, mergeAtriumMessages as c, useAtriumI18n as l, useAtriumIntegration as u } from "@refactor-circus/atrium";
import { UAlert as ee, UBadge as d, UButton as te, UCard as f, UEmpty as ne, UForm as p, UFormField as m, UInput as h, USkeleton as g } from "@refactor-circus/atrium/ui";
import { Fragment as _, computed as v, createBlock as y, createCommentVNode as b, createElementBlock as x, createElementVNode as S, createVNode as C, defineComponent as w, isRef as T, openBlock as E, ref as D, renderList as O, toDisplayString as k, unref as A, withCtx as j } from "vue";
import { useI18n as M } from "vue-i18n";
// src/components/CortexSettingsPanel.vue?vue&type=script&setup=true&lang.ts
var N = { "data-testid": "cortex-settings-panel" }, P = { class: "flex flex-col gap-4" }, F = { class: "grid gap-4 sm:grid-cols-2 lg:grid-cols-4" }, I = { class: "text-sm text-muted" }, L = { class: "text-2xl font-semibold" }, R = { class: "font-medium" }, z = /* @__PURE__ */ w({
	__name: "CortexSettingsPanel",
	props: { data: {} },
	setup(t) {
		let { t: i } = M(), { data: a, pending: o, failed: s, status: c } = e(async (e) => n(await e.GET("/settings-summary")).data), l = v(() => a.value ? [
			{
				key: "providers",
				label: i("cortex.settings.providers"),
				value: a.value.providers.length
			},
			{
				key: "tools",
				label: i("cortex.settings.tools"),
				value: a.value.tool_count
			},
			{
				key: "agents",
				label: i("cortex.settings.concrete_agents"),
				value: a.value.agent_count
			},
			{
				key: "servers",
				label: i("cortex.settings.servers"),
				value: a.value.server_count
			}
		] : []);
		return (e, t) => {
			let n = f, u = d;
			return E(), x("div", N, [C(r, {
				ready: !A(o) && A(a) !== null,
				failed: A(s),
				status: A(c)
			}, {
				default: j(() => [S("div", P, [S("div", F, [(E(!0), x(_, null, O(A(l), (e) => (E(), y(n, {
					key: e.key,
					"data-testid": `cortex-stat-${e.key}`
				}, {
					default: j(() => [S("p", I, k(e.label), 1), S("p", L, k(e.value), 1)]),
					_: 2
				}, 1032, ["data-testid"]))), 128))]), C(n, null, {
					header: j(() => [S("h3", R, k(A(i)("cortex.settings.cache")), 1)]),
					default: j(() => [C(u, {
						color: A(a)?.cache_enabled ? "success" : "neutral",
						variant: "subtle",
						label: A(a)?.cache_enabled ? A(i)("cortex.settings.enabled") : A(i)("cortex.settings.disabled"),
						"data-status": A(a)?.cache_enabled ? "enabled" : "disabled",
						"data-testid": "cortex-cache"
					}, null, 8, [
						"color",
						"label",
						"data-status"
					])]),
					_: 1
				})])]),
				_: 1
			}, 8, [
				"ready",
				"failed",
				"status"
			])]);
		};
	}
});
// src/components/hooks/hooks.ts
function B(e) {
	return typeof e == "number" && Number.isFinite(e) ? String(e) : typeof e == "string" && e !== "" ? e : null;
}
function V(e) {
	return typeof e == "string" && e !== "" ? e : null;
}
function H(e, t) {
	let n = V(e.owner_type), r = B(t);
	return n && r ? {
		type: n,
		id: r
	} : null;
}
// src/components/hooks/CortexRedirectDomains.vue?vue&type=script&setup=true&lang.ts
var U = {
	class: "font-medium",
	"data-testid": "hooks-redirect-domains-title"
}, W = {
	class: "mt-1 text-sm text-muted",
	"data-testid": "hooks-redirect-domains-description"
}, G = {
	key: 1,
	class: "divide-y divide-default text-sm"
}, K = ["data-testid"], q = { class: "truncate text-xs" }, J = /* @__PURE__ */ w({
	__name: "CortexRedirectDomains",
	props: {
		data: {},
		record: {}
	},
	setup(r) {
		let o = r, { t: s } = M(), c = t(), l = c.busy, u = v(() => H(o.data, o.record)), d = D(""), { client: w, data: N, pending: P, failed: F, status: I, reload: L } = e(async (e) => u.value ? n(await e.GET("/redirect-domains", { params: { query: {
			owner_type: u.value.type,
			owner_id: u.value.id
		} } })).data : []), R = v(() => N.value ?? []);
		async function z() {
			let e = u.value;
			w && e && await c.run(async () => n(await w.POST("/redirect-domains", { body: {
				domain: d.value,
				owner_type: e.type,
				owner_id: e.id
			} }))) && (d.value = "", await L());
		}
		async function B(e) {
			w && await c.run(async () => n(await w.DELETE("/redirect-domains/{domain}", { params: { path: { domain: e.id } } }))) && await L();
		}
		return (e, t) => {
			let n = a, r = ee, o = g, v = ne, D = te, M = h, N = m, L = p, V = f;
			return A(u) && A(w) ? (E(), y(V, {
				key: 0,
				ui: { body: "space-y-3" },
				"data-testid": "hooks-redirect-domains"
			}, {
				header: j(() => [S("h3", U, k(A(s)("hooks.redirect_domains.title")), 1), S("p", W, k(A(s)("hooks.redirect_domains.description")), 1)]),
				default: j(() => [A(I) === 403 ? (E(), y(n, {
					key: 0,
					compact: ""
				})) : A(F) ? (E(), y(r, {
					key: 1,
					color: "error",
					variant: "subtle",
					title: A(s)("hooks.redirect_domains.load_failed"),
					"data-testid": "hooks-redirect-domains-failed"
				}, null, 8, ["title"])) : A(P) ? (E(), y(o, {
					key: 2,
					class: "h-16"
				})) : (E(), x(_, { key: 3 }, [
					C(i, {
						forbidden: A(c).forbidden.value,
						message: A(c).message.value
					}, null, 8, ["forbidden", "message"]),
					A(R).length === 0 ? (E(), y(v, {
						key: 0,
						icon: "i-lucide-globe",
						title: A(s)("hooks.redirect_domains.empty"),
						size: "xs",
						variant: "naked",
						ui: { root: "py-6" },
						role: "status",
						"data-testid": "hooks-redirect-domains-empty"
					}, null, 8, ["title"])) : (E(), x("ul", G, [(E(!0), x(_, null, O(A(R), (e) => (E(), x("li", {
						key: e.id,
						class: "flex items-center justify-between gap-2 py-2",
						"data-testid": `hooks-redirect-domain-${e.id}`
					}, [S("code", q, k(e.domain), 1), C(D, {
						size: "xs",
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-trash",
						label: A(s)("hooks.redirect_domains.remove"),
						loading: A(l),
						"data-testid": `hooks-remove-redirect-domain-${e.id}`,
						onClick: (t) => B(e)
					}, null, 8, [
						"label",
						"loading",
						"data-testid",
						"onClick"
					])], 8, K))), 128))])),
					C(L, {
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "hooks-add-redirect-domain-form",
						onSubmit: z
					}, {
						default: j(() => [C(N, {
							label: A(s)("hooks.redirect_domains.domain"),
							error: A(c).error("domain"),
							class: "min-w-48 flex-1",
							required: ""
						}, {
							default: j(() => [C(M, {
								modelValue: A(d),
								"onUpdate:modelValue": t[0] ||= (e) => T(d) ? d.value = e : null,
								placeholder: "https://claude.ai",
								class: "w-full",
								"data-testid": "hooks-redirect-domain"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]), C(D, {
							type: "submit",
							icon: "i-lucide-plus",
							label: A(s)("hooks.redirect_domains.add"),
							loading: A(l),
							"data-testid": "hooks-add-redirect-domain"
						}, null, 8, ["label", "loading"])]),
						_: 1
					})
				], 64))]),
				_: 1
			})) : b("", !0);
		};
	}
}), Y = { cortex: {
	actions: "Actions",
	edit: "Edit",
	delete: "Delete",
	previous: "Previous page",
	next: "Next page",
	page: "Page {page} of {last}",
	state: {
		not_found: "Not found.",
		load_failed: "Cortex could not be reached.",
		action_forbidden: "You may not do that."
	},
	agent: {
		name: "Name",
		slug: "Slug",
		slug_hint: "Letters, numbers, dashes and underscores. This cannot be changed later.",
		description: "Description",
		prompt: "Prompt",
		prompt_hint: "Saving a changed prompt adds a new version and publishes it. Earlier versions can be republished below.",
		provider: "Provider",
		provider_default: "Default",
		model: "Model",
		settings: "Settings",
		temperature: "Temperature",
		max_steps: "Max steps",
		max_tokens: "Max tokens",
		top_p: "Top p",
		tools: "Tools",
		sub_agents: "Sub-agents",
		concrete_sub_agents: "Concrete sub-agents",
		save: "Save agent",
		cancel: "Cancel"
	},
	virtual_agents: {
		title: "Virtual agents",
		new: "New virtual agent",
		empty: "No virtual agents yet.",
		confirm_delete: "Are you sure? This cannot be undone."
	},
	concrete_agents: {
		title: "Concrete agents",
		description: "Agents defined in code. Their prompt and tools can be overridden here.",
		empty: "No concrete agents are registered.",
		not_overridable: "This agent does not support overrides, so its prompt cannot be overridden here."
	},
	tools: {
		title: "Tools",
		empty: "No tools match.",
		search: "Search tools",
		selected_only: "Selected only",
		selected: "{count} selected",
		all_tags: "All tags",
		tags: "Tags",
		description: "Description",
		description_title: "Description of {tool}",
		edit_description: "Description",
		overridden: "Overridden",
		count: "Tools: {count}",
		save: "Save tools",
		restore: "Use the tools declared in code",
		confirm_restore: "Use the tools declared in code again? The saved tool list is removed."
	},
	servers: {
		title: "Servers",
		empty: "No MCP servers are registered.",
		instructions: "Instructions",
		instructions_title: "Instructions of {server}",
		edit_instructions: "Instructions and tools",
		missing_tools: "Missing: {tools}",
		confirm_empty: "Serve no tools",
		confirm_empty_needed: "Check \"Serve no tools\" to save with no tool checked: the server then serves no tools at all."
	},
	versions: {
		title: "Versions",
		none: "No versions yet.",
		published: "Published",
		overridden: "Overridden (v{version})",
		from_code: "From code",
		override_hint: "The published override replaces what is declared in code.",
		no_override_hint: "No override exists, so the value declared in code is used.",
		view: "View",
		hide: "Hide",
		publish: "Publish",
		new: "New version",
		content: "Content",
		publish_now: "Publish it now",
		add: "Save version",
		remove: "Remove override",
		confirm_remove: "Remove the override? The value declared in code is used again."
	},
	redirect_domains: {
		title: "Redirect domains",
		description: "Every origin MCP clients may register OAuth redirect URIs on: global ones added here, and those organizations and users add. Each user still approves the client.",
		add: "Add domain",
		domain: "Domain",
		domain_hint: "A host, origin or full redirect URL; only the origin is kept. A bare host is taken as https.",
		source: "Source",
		global: "Global",
		created: "Created",
		remove: "Remove",
		empty: "No redirect domains yet."
	},
	run: {
		title: "Run agent",
		run: "Run",
		none: "There are no agents you may run.",
		agent: "Agent",
		select_agent: "Select an agent",
		input: "Input",
		response: "Response",
		usage: "Usage"
	},
	settings: {
		providers: "Providers",
		tools: "Registered tools",
		concrete_agents: "Concrete agents",
		servers: "MCP servers",
		cache: "Cache",
		enabled: "Enabled",
		disabled: "Disabled"
	},
	search: {
		virtual_agent: "Virtual agent",
		concrete_agent: "Concrete agent"
	}
} }, X = { hooks: { redirect_domains: {
	title: "Redirect domains",
	description: "The OAuth redirect domains this record's MCP clients may use.",
	empty: "No redirect domains yet.",
	domain: "Domain",
	add: "Add domain",
	remove: "Remove",
	load_failed: "The redirect domains could not be loaded."
} } }, re = [
	{
		path: "/cortex/concrete-agents/:agent",
		name: "cortex-concrete-agents-agent",
		component: () => import("./chunks/_agent_-CMC_XlDl.js")
	},
	{
		path: "/cortex/concrete-agents",
		name: "cortex-concrete-agents",
		component: () => import("./chunks/concrete-agents-2glwuWH-.js")
	},
	{
		path: "/cortex/redirect-domains",
		name: "cortex-redirect-domains",
		component: () => import("./chunks/redirect-domains-B2uktsMr.js")
	},
	{
		path: "/cortex/run",
		name: "cortex-run",
		component: () => import("./chunks/run-BZmdUWjV.js")
	},
	{
		path: "/cortex/servers/:server/instructions",
		name: "cortex-servers-server-instructions",
		component: () => import("./chunks/instructions-DzTOyDbr.js")
	},
	{
		path: "/cortex/servers",
		name: "cortex-servers",
		component: () => import("./chunks/servers-kbFT8Jgv.js")
	},
	{
		path: "/cortex/tools/:tool/description",
		name: "cortex-tools-tool-description",
		component: () => import("./chunks/description-BKH0H77G.js")
	},
	{
		path: "/cortex/tools",
		name: "cortex-tools",
		component: () => import("./chunks/tools-C7S-n2ol.js")
	},
	{
		path: "/cortex/virtual-agents/:slug",
		name: "cortex-virtual-agents-slug",
		component: () => import("./chunks/_slug_-Dc41BXEc.js")
	},
	{
		path: "/cortex/virtual-agents/create",
		name: "cortex-virtual-agents-create",
		component: () => import("./chunks/create-CuCqENrP.js")
	},
	{
		path: "/cortex/virtual-agents",
		name: "cortex-virtual-agents",
		component: () => import("./chunks/virtual-agents-BN3pNjmG.js")
	}
], Z = {
	"cortex.virtual-agents": "cortex-virtual-agents",
	"cortex.virtual-agent-create": "cortex-virtual-agents-create",
	"cortex.virtual-agent": "cortex-virtual-agents-slug",
	"cortex.concrete-agents": "cortex-concrete-agents",
	"cortex.concrete-agent": "cortex-concrete-agents-agent",
	"cortex.tools": "cortex-tools",
	"cortex.tool-description": "cortex-tools-tool-description",
	"cortex.servers": "cortex-servers",
	"cortex.server-instructions": "cortex-servers-server-instructions",
	"cortex.redirect-domains": "cortex-redirect-domains",
	"cortex.run": "cortex-run"
};
function Q(e) {
	return e.type === "concrete_agent" ? `/cortex/concrete-agents/${encodeURIComponent(e.key)}` : `/cortex/virtual-agents/${encodeURIComponent(e.key)}`;
}
async function $(e, { limit: t }) {
	let { client: n } = u("cortex"), { t: r } = l();
	return n ? s(await n.GET("/search", { params: { query: { q: e } } })).data.slice(0, t).map((e) => ({
		title: e.name,
		subtitle: e.type === "concrete_agent" ? r("cortex.search.concrete_agent") : r("cortex.search.virtual_agent"),
		icon: e.type === "concrete_agent" ? "i-lucide-cpu" : "i-lucide-sparkles",
		group: null,
		link: {
			path: Q(e),
			external: !1
		}
	})) : [];
}
var ie = o({
	key: "cortex",
	routes: re,
	screens: Z,
	extensions: {
		"cortex.mcp": J,
		"cortex.redirect-domains": J
	},
	settingsPanels: { cortex: z },
	searchSources: { cortex: $ },
	messages: { en: c(Y, X) }
});
export { Q as cortexAgentPath, ie as default, Z as screens, $ as searchCortex };
