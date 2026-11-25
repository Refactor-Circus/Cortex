import { a as e, t } from "./cortex-Bp7gHDgF.js";
import { t as n } from "./CortexLoad-Bz-c2t3K.js";
import { t as r } from "./CortexPage-CtJD4yvP.js";
import { UBadge as i, UButton as a, UEmpty as o, ULink as s, UTable as c } from "@refactor-circus/atrium/ui";
import { computed as l, createBlock as u, createElementBlock as d, createElementVNode as f, createTextVNode as p, createVNode as m, defineComponent as h, openBlock as g, toDisplayString as _, unref as v, withCtx as y } from "vue";
import { useI18n as b } from "vue-i18n";
// src/pages/concrete-agents/index.vue?vue&type=script&setup=true&lang.ts
var x = {
	key: 1,
	class: "text-muted"
}, S = { class: "flex justify-end gap-2" }, C = /* @__PURE__ */ h({
	__name: "index",
	setup(h) {
		let { t: C } = b(), { data: w, pending: T, failed: E, status: D } = e(async (e) => t(await e.GET("/concrete-agents")).data), O = l(() => w.value ?? []), k = l(() => [
			{
				accessorKey: "name",
				header: C("cortex.agent.name")
			},
			{
				id: "prompt",
				header: C("cortex.agent.prompt")
			},
			{
				id: "tools",
				header: C("cortex.agent.tools")
			},
			{
				id: "actions",
				header: C("cortex.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		return (e, t) => {
			let l = o, h = s, b = i, w = a, A = c;
			return g(), u(r, {
				title: v(C)("cortex.concrete_agents.title"),
				"screen-key": "cortex.concrete-agents",
				description: v(C)("cortex.concrete_agents.description")
			}, {
				default: y(() => [m(n, {
					ready: !v(T),
					failed: v(E),
					status: v(D)
				}, {
					default: y(() => [v(O).length === 0 ? (g(), u(l, {
						key: 0,
						icon: "i-lucide-bot",
						title: v(C)("cortex.concrete_agents.empty"),
						variant: "naked",
						role: "status",
						"data-testid": "cortex-empty"
					}, null, 8, ["title"])) : (g(), u(A, {
						key: 1,
						data: v(O),
						columns: v(k),
						"data-testid": "cortex-concrete-agents"
					}, {
						"name-cell": y(({ row: e }) => [m(h, {
							to: `/cortex/concrete-agents/${e.original.name}`,
							class: "font-medium hover:underline",
							"data-testid": `cortex-agent-${e.original.name}`
						}, {
							default: y(() => [p(_(e.original.name), 1)]),
							_: 2
						}, 1032, ["to", "data-testid"])]),
						"prompt-cell": y(({ row: e }) => [e.original.published_version === null ? (g(), u(b, {
							key: 1,
							color: "neutral",
							variant: "subtle",
							label: v(C)("cortex.versions.from_code"),
							"data-status": "from_code"
						}, null, 8, ["label"])) : (g(), u(b, {
							key: 0,
							color: "success",
							variant: "subtle",
							label: v(C)("cortex.versions.overridden", { version: e.original.published_version }),
							"data-status": "overridden"
						}, null, 8, ["label"]))]),
						"tools-cell": y(({ row: e }) => [e.original.tools_overridden ? (g(), u(b, {
							key: 0,
							color: "success",
							variant: "subtle",
							label: v(C)("cortex.tools.overridden")
						}, null, 8, ["label"])) : (g(), d("span", x, _(v(C)("cortex.tools.count", { count: e.original.tools.length })), 1))]),
						"actions-cell": y(({ row: e }) => [f("div", S, [m(w, {
							size: "xs",
							variant: "outline",
							icon: "i-lucide-pencil",
							to: `/cortex/concrete-agents/${e.original.name}`,
							label: v(C)("cortex.edit")
						}, null, 8, ["to", "label"]), m(w, {
							size: "xs",
							variant: "outline",
							icon: "i-lucide-play",
							to: `/cortex/run?agent=concrete:${e.original.name}`,
							label: v(C)("cortex.run.run"),
							"data-testid": `cortex-run-${e.original.name}`
						}, null, 8, [
							"to",
							"label",
							"data-testid"
						])])]),
						_: 1
					}, 8, ["data", "columns"]))]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["title", "description"]);
		};
	}
});
export { C as default };
