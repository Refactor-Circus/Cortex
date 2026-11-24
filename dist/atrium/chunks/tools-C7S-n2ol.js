import { a as e, t } from "./cortex-Bp7gHDgF.js";
import { t as n } from "./CortexLoad-Bz-c2t3K.js";
import { t as r } from "./CortexPage-CtJD4yvP.js";
import { UBadge as i, UButton as a, UEmpty as o, UInput as s, UTable as c } from "@refactor-circus/atrium/ui";
import { Fragment as l, computed as u, createBlock as d, createElementBlock as f, createElementVNode as p, createVNode as m, defineComponent as h, isRef as g, openBlock as _, ref as v, renderList as y, toDisplayString as b, unref as x, watch as S, withCtx as C } from "vue";
import { useI18n as w } from "vue-i18n";
import { useRoute as T, useRouter as E } from "vue-router";
// src/pages/tools/index.vue?vue&type=script&setup=true&lang.ts
var D = { class: "flex flex-col gap-4" }, O = {
	class: "flex flex-wrap items-center gap-2",
	"data-testid": "cortex-tags"
}, k = ["data-testid"], A = { class: "flex flex-wrap gap-1" }, j = /* @__PURE__ */ h({
	__name: "index",
	setup(h) {
		let { t: j } = w(), M = T(), N = E(), P = u(() => typeof M.query.tag == "string" && M.query.tag !== "" ? M.query.tag : null), F = v(""), { data: I, pending: L, failed: R, status: z, reload: B } = e(async (e) => ({
			tools: t(await e.GET("/tools", { params: { query: P.value === null ? {} : { tag: P.value } } })).data,
			tags: P.value === null ? null : t(await e.GET("/tools")).data.flatMap((e) => e.tags)
		}));
		S(P, () => void B());
		let V = u(() => [...new Set(I.value?.tags ?? (I.value?.tools ?? []).flatMap((e) => e.tags))].sort()), H = u(() => (I.value?.tools ?? []).filter((e) => F.value === "" || e.name.toLowerCase().includes(F.value.toLowerCase()))), U = u(() => [
			{
				accessorKey: "name",
				header: j("cortex.agent.name")
			},
			{
				accessorKey: "description",
				header: j("cortex.tools.description")
			},
			{
				id: "tags",
				header: j("cortex.tools.tags")
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
		function W(e) {
			N.push({ query: {
				...M.query,
				tag: e ?? void 0
			} });
		}
		return (e, t) => {
			let u = a, h = s, v = o, S = i, w = c;
			return _(), d(r, {
				title: x(j)("cortex.tools.title"),
				"screen-key": "cortex.tools"
			}, {
				default: C(() => [m(n, {
					ready: !x(L),
					failed: x(R),
					status: x(z)
				}, {
					default: C(() => [p("div", D, [p("div", O, [
						m(u, {
							size: "xs",
							color: "neutral",
							variant: x(P) === null ? "solid" : "outline",
							label: x(j)("cortex.tools.all_tags"),
							"data-testid": "cortex-tag-all",
							onClick: t[0] ||= (e) => W(null)
						}, null, 8, ["variant", "label"]),
						(_(!0), f(l, null, y(x(V), (e) => (_(), d(u, {
							key: e,
							size: "xs",
							color: "neutral",
							variant: x(P) === e ? "solid" : "outline",
							label: e,
							"data-testid": `cortex-tag-${e}`,
							onClick: (t) => W(e)
						}, null, 8, [
							"variant",
							"label",
							"data-testid",
							"onClick"
						]))), 128)),
						m(h, {
							modelValue: x(F),
							"onUpdate:modelValue": t[1] ||= (e) => g(F) ? F.value = e : null,
							class: "ml-auto",
							icon: "i-lucide-search",
							placeholder: x(j)("cortex.tools.search"),
							"data-testid": "cortex-tool-search"
						}, null, 8, ["modelValue", "placeholder"])
					]), x(H).length === 0 ? (_(), d(v, {
						key: 0,
						icon: "i-lucide-wrench",
						title: x(j)("cortex.tools.empty"),
						variant: "naked",
						role: "status",
						"data-testid": "cortex-empty"
					}, null, 8, ["title"])) : (_(), d(w, {
						key: 1,
						data: x(H),
						columns: x(U),
						"data-testid": "cortex-tools"
					}, {
						"name-cell": C(({ row: e }) => [p("code", {
							class: "text-xs",
							"data-testid": `cortex-tool-${e.original.name}`
						}, b(e.original.name), 9, k)]),
						"tags-cell": C(({ row: e }) => [p("div", A, [(_(!0), f(l, null, y(e.original.tags, (e) => (_(), d(S, {
							key: e,
							color: "neutral",
							variant: "subtle",
							size: "sm",
							label: e
						}, null, 8, ["label"]))), 128))])]),
						"actions-cell": C(({ row: e }) => [m(u, {
							size: "xs",
							variant: "outline",
							icon: "i-lucide-file-text",
							to: `/cortex/tools/${e.original.name}/description`,
							label: x(j)("cortex.tools.edit_description"),
							"data-testid": `cortex-describe-${e.original.name}`
						}, null, 8, [
							"to",
							"label",
							"data-testid"
						])]),
						_: 1
					}, 8, ["data", "columns"]))])]),
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
