import { a as e, i as t, t as n } from "./cortex-Bp7gHDgF.js";
import { t as r } from "./CortexActionError-BnFnixGr.js";
import { t as i } from "./CortexLoad-Bz-c2t3K.js";
import { t as a } from "./CortexPage-CtJD4yvP.js";
import { UBadge as o, UButton as s, UCard as c, UEmpty as l, UForm as u, UFormField as d, UInput as f, UTable as p } from "@refactor-circus/atrium/ui";
import { computed as m, createBlock as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, isRef as x, openBlock as S, ref as C, toDisplayString as w, unref as T, withCtx as E } from "vue";
import { useI18n as D } from "vue-i18n";
// src/pages/redirect-domains.vue?vue&type=script&setup=true&lang.ts
var O = { class: "flex flex-col gap-4" }, k = { class: "font-medium" }, A = ["data-testid"], j = { key: 0 }, M = { class: "text-xs text-muted" }, N = /* @__PURE__ */ b({
	__name: "redirect-domains",
	setup(b) {
		let { t: N } = D(), P = t(), F = P.busy, I = C(""), { client: L, data: R, pending: z, failed: B, status: V, reload: H } = e(async (e) => n(await e.GET("/redirect-domains")).data), U = m(() => R.value ?? []), W = m(() => [
			{
				accessorKey: "domain",
				header: N("cortex.redirect_domains.domain")
			},
			{
				id: "source",
				header: N("cortex.redirect_domains.source")
			},
			{
				id: "created",
				header: N("cortex.redirect_domains.created")
			},
			{
				id: "actions",
				header: N("cortex.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		async function G() {
			L && await P.run(async () => n(await L.POST("/redirect-domains", { body: { domain: I.value } }))) && (I.value = "", await H());
		}
		async function K(e) {
			L && await P.run(async () => n(await L.DELETE("/redirect-domains/{domain}", { params: { path: { domain: e.id } } }))) && await H();
		}
		return (e, t) => {
			let n = f, m = d, b = s, C = u, D = c, L = l, R = o, H = p;
			return S(), h(a, {
				title: T(N)("cortex.redirect_domains.title"),
				"screen-key": "cortex.redirect-domains",
				description: T(N)("cortex.redirect_domains.description")
			}, {
				default: E(() => [y(i, {
					ready: !T(z),
					failed: T(B),
					status: T(V)
				}, {
					default: E(() => [_("div", O, [
						y(r, {
							forbidden: T(P).forbidden.value,
							message: T(P).message.value
						}, null, 8, ["forbidden", "message"]),
						y(D, null, {
							header: E(() => [_("h3", k, w(T(N)("cortex.redirect_domains.add")), 1)]),
							default: E(() => [y(C, {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "cortex-add-domain-form",
								onSubmit: G
							}, {
								default: E(() => [y(m, {
									label: T(N)("cortex.redirect_domains.domain"),
									hint: T(N)("cortex.redirect_domains.domain_hint"),
									error: T(P).error("domain"),
									class: "w-80",
									required: ""
								}, {
									default: E(() => [y(n, {
										modelValue: T(I),
										"onUpdate:modelValue": t[0] ||= (e) => x(I) ? I.value = e : null,
										placeholder: "https://claude.ai",
										class: "w-full",
										"data-testid": "cortex-domain"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), y(b, {
									type: "submit",
									icon: "i-lucide-plus",
									label: T(N)("cortex.redirect_domains.add"),
									loading: T(F),
									"data-testid": "cortex-add-domain"
								}, null, 8, ["label", "loading"])]),
								_: 1
							})]),
							_: 1
						}),
						T(U).length === 0 ? (S(), h(L, {
							key: 0,
							icon: "i-lucide-globe",
							title: T(N)("cortex.redirect_domains.empty"),
							variant: "naked",
							role: "status",
							"data-testid": "cortex-empty"
						}, null, 8, ["title"])) : (S(), h(H, {
							key: 1,
							data: T(U),
							columns: T(W),
							"data-testid": "cortex-redirect-domains"
						}, {
							"domain-cell": E(({ row: e }) => [_("code", {
								class: "text-xs",
								"data-testid": `cortex-domain-${e.original.id}`
							}, w(e.original.domain), 9, A)]),
							"source-cell": E(({ row: e }) => [e.original.owner_type ? (S(), g("span", j, [v(w(e.original.owner_id) + " ", 1), _("span", M, w(e.original.owner_type), 1)])) : (S(), h(R, {
								key: 1,
								color: "neutral",
								variant: "subtle",
								label: T(N)("cortex.redirect_domains.global")
							}, null, 8, ["label"]))]),
							"created-cell": E(({ row: e }) => [v(w(e.original.created_at ?? "—"), 1)]),
							"actions-cell": E(({ row: e }) => [y(b, {
								size: "xs",
								color: "neutral",
								variant: "ghost",
								icon: "i-lucide-trash",
								label: T(N)("cortex.redirect_domains.remove"),
								"data-testid": `cortex-remove-domain-${e.original.id}`,
								onClick: (t) => K(e.original)
							}, null, 8, [
								"label",
								"data-testid",
								"onClick"
							])]),
							_: 1
						}, 8, ["data", "columns"]))
					])]),
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
export { N as default };
