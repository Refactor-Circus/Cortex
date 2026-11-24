import { a as e, t } from "./cortex-Bp7gHDgF.js";
import { t as n } from "./CortexLoad-Bz-c2t3K.js";
import { t as r } from "./CortexPage-CtJD4yvP.js";
import { UBadge as i, UButton as a, UEmpty as o, UTable as s } from "@refactor-circus/atrium/ui";
import { computed as c, createBlock as l, createCommentVNode as u, createElementVNode as d, createVNode as f, defineComponent as p, openBlock as m, toDisplayString as h, unref as g, withCtx as _ } from "vue";
import { useI18n as v } from "vue-i18n";
// src/pages/servers/index.vue?vue&type=script&setup=true&lang.ts
var y = ["data-testid"], b = { class: "flex flex-wrap items-center gap-2" }, x = /* @__PURE__ */ p({
	__name: "index",
	setup(p) {
		let { t: x } = v(), { data: S, pending: C, failed: w, status: T } = e(async (e) => t(await e.GET("/servers")).data), E = c(() => S.value ?? []), D = c(() => [
			{
				accessorKey: "name",
				header: x("cortex.agent.name")
			},
			{
				id: "tools",
				header: x("cortex.agent.tools")
			},
			{
				id: "actions",
				header: x("cortex.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		return (e, t) => {
			let c = o, p = i, v = a, S = s;
			return m(), l(r, {
				title: g(x)("cortex.servers.title"),
				"screen-key": "cortex.servers"
			}, {
				default: _(() => [f(n, {
					ready: !g(C),
					failed: g(w),
					status: g(T)
				}, {
					default: _(() => [g(E).length === 0 ? (m(), l(c, {
						key: 0,
						icon: "i-lucide-server",
						title: g(x)("cortex.servers.empty"),
						variant: "naked",
						role: "status",
						"data-testid": "cortex-empty"
					}, null, 8, ["title"])) : (m(), l(S, {
						key: 1,
						data: g(E),
						columns: g(D),
						"data-testid": "cortex-servers"
					}, {
						"name-cell": _(({ row: e }) => [d("code", {
							class: "text-xs",
							"data-testid": `cortex-server-${e.original.name}`
						}, h(e.original.name), 9, y)]),
						"tools-cell": _(({ row: e }) => [d("div", b, [
							d("span", null, h(g(x)("cortex.tools.count", { count: e.original.tools.length })), 1),
							e.original.tools_overridden ? (m(), l(p, {
								key: 0,
								color: "success",
								variant: "subtle",
								label: g(x)("cortex.tools.overridden")
							}, null, 8, ["label"])) : u("", !0),
							e.original.missing_tools.length ? (m(), l(p, {
								key: 1,
								color: "warning",
								variant: "subtle",
								label: g(x)("cortex.servers.missing_tools", { tools: e.original.missing_tools.join(", ") }),
								"data-testid": "cortex-missing-tools"
							}, null, 8, ["label"])) : u("", !0)
						])]),
						"actions-cell": _(({ row: e }) => [f(v, {
							size: "xs",
							variant: "outline",
							icon: "i-lucide-file-text",
							to: `/cortex/servers/${e.original.name}/instructions`,
							label: g(x)("cortex.servers.edit_instructions"),
							"data-testid": `cortex-instructions-${e.original.name}`
						}, null, 8, [
							"to",
							"label",
							"data-testid"
						])]),
						_: 1
					}, 8, ["data", "columns"]))]),
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
export { x as default };
