import { a as e, i as t, t as n } from "./cortex-Bp7gHDgF.js";
import { t as r } from "./CortexActionError-BnFnixGr.js";
import { t as i } from "./CortexLoad-Bz-c2t3K.js";
import { t as a } from "./CortexPage-CtJD4yvP.js";
import { UButton as o, UEmpty as s, ULink as c, UTable as l } from "@refactor-circus/atrium/ui";
import { computed as u, createBlock as d, createCommentVNode as f, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as _, openBlock as v, toDisplayString as y, unref as b, watch as x, withCtx as S } from "vue";
import { useI18n as C } from "vue-i18n";
import { useRoute as w, useRouter as T } from "vue-router";
// src/pages/virtual-agents/index.vue?vue&type=script&setup=true&lang.ts
var E = { class: "flex flex-col gap-4" }, D = { class: "text-xs" }, O = { class: "flex justify-end gap-2" }, k = {
	key: 2,
	class: "flex items-center gap-2",
	"data-testid": "cortex-pages"
}, A = { class: "text-sm text-muted" }, j = /* @__PURE__ */ _({
	__name: "index",
	setup(_) {
		let { t: j } = C(), M = w(), N = T(), P = t(), F = u(() => Math.max(1, Number(M.query.page) || 1)), { client: I, data: L, pending: R, failed: z, status: B, reload: V } = e(async (e) => n(await e.GET("/virtual-agents", { params: { query: { page: F.value } } })));
		x(F, () => void V());
		let H = u(() => L.value?.data ?? []), U = u(() => L.value?.meta?.last_page ?? 1), W = u(() => [
			{
				accessorKey: "name",
				header: j("cortex.agent.name")
			},
			{
				accessorKey: "slug",
				header: j("cortex.agent.slug")
			},
			{
				id: "prompt",
				header: j("cortex.agent.prompt")
			},
			{
				id: "provider",
				header: j("cortex.agent.provider")
			},
			{
				id: "model",
				header: j("cortex.agent.model")
			},
			{
				id: "actions",
				header: j("cortex.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		function G(e) {
			N.push({ query: {
				...M.query,
				page: e > 1 ? String(e) : void 0
			} });
		}
		async function K(e) {
			I && window.confirm(j("cortex.virtual_agents.confirm_delete")) && await P.run(async () => n(await I.DELETE("/virtual-agents/{agent}", { params: { path: { agent: e.slug } } }))) && await V();
		}
		return (e, t) => {
			let n = o, u = s, _ = c, x = l;
			return v(), d(a, {
				title: b(j)("cortex.virtual_agents.title"),
				"screen-key": "cortex.virtual-agents"
			}, {
				actions: S(() => [g(n, {
					to: "/cortex/virtual-agents/create",
					icon: "i-lucide-plus",
					label: b(j)("cortex.virtual_agents.new"),
					"data-testid": "cortex-new-agent"
				}, null, 8, ["label"])]),
				default: S(() => [g(i, {
					ready: !b(R),
					failed: b(z),
					status: b(B)
				}, {
					default: S(() => [m("div", E, [
						g(r, {
							forbidden: b(P).forbidden.value,
							message: b(P).message.value
						}, null, 8, ["forbidden", "message"]),
						b(H).length === 0 ? (v(), d(u, {
							key: 0,
							icon: "i-lucide-bot",
							title: b(j)("cortex.virtual_agents.empty"),
							variant: "naked",
							role: "status",
							"data-testid": "cortex-empty"
						}, null, 8, ["title"])) : (v(), d(x, {
							key: 1,
							data: b(H),
							columns: b(W),
							"data-testid": "cortex-virtual-agents"
						}, {
							"name-cell": S(({ row: e }) => [g(_, {
								to: `/cortex/virtual-agents/${e.original.slug}`,
								class: "font-medium hover:underline",
								"data-testid": `cortex-agent-${e.original.slug}`
							}, {
								default: S(() => [h(y(e.original.name), 1)]),
								_: 2
							}, 1032, ["to", "data-testid"])]),
							"slug-cell": S(({ row: e }) => [m("code", D, y(e.original.slug), 1)]),
							"prompt-cell": S(({ row: e }) => [h(y(e.original.published_version ? `v${e.original.published_version}` : "—"), 1)]),
							"provider-cell": S(({ row: e }) => [h(y(e.original.provider ?? "—"), 1)]),
							"model-cell": S(({ row: e }) => [h(y(e.original.model ?? "—"), 1)]),
							"actions-cell": S(({ row: e }) => [m("div", O, [
								g(n, {
									size: "xs",
									variant: "outline",
									icon: "i-lucide-pencil",
									to: `/cortex/virtual-agents/${e.original.slug}`,
									label: b(j)("cortex.edit"),
									"data-testid": `cortex-edit-${e.original.slug}`
								}, null, 8, [
									"to",
									"label",
									"data-testid"
								]),
								g(n, {
									size: "xs",
									variant: "outline",
									icon: "i-lucide-play",
									to: `/cortex/run?agent=virtual:${e.original.slug}`,
									label: b(j)("cortex.run.run"),
									"data-testid": `cortex-run-${e.original.slug}`
								}, null, 8, [
									"to",
									"label",
									"data-testid"
								]),
								g(n, {
									size: "xs",
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash",
									label: b(j)("cortex.delete"),
									"data-testid": `cortex-delete-${e.original.slug}`,
									onClick: (t) => K(e.original)
								}, null, 8, [
									"label",
									"data-testid",
									"onClick"
								])
							])]),
							_: 1
						}, 8, ["data", "columns"])),
						b(U) > 1 ? (v(), p("div", k, [
							g(n, {
								size: "xs",
								variant: "outline",
								icon: "i-lucide-chevron-left",
								disabled: b(F) <= 1,
								"aria-label": b(j)("cortex.previous"),
								"data-testid": "cortex-previous",
								onClick: t[0] ||= (e) => G(b(F) - 1)
							}, null, 8, ["disabled", "aria-label"]),
							m("span", A, y(b(j)("cortex.page", {
								page: b(F),
								last: b(U)
							})), 1),
							g(n, {
								size: "xs",
								variant: "outline",
								icon: "i-lucide-chevron-right",
								disabled: b(F) >= b(U),
								"aria-label": b(j)("cortex.next"),
								"data-testid": "cortex-next",
								onClick: t[1] ||= (e) => G(b(F) + 1)
							}, null, 8, ["disabled", "aria-label"])
						])) : f("", !0)
					])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["title"]);
		};
	}
});
export { j as default };
