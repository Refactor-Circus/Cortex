import { i as e } from "./cortex-Bp7gHDgF.js";
import { t } from "./CortexActionError-BnFnixGr.js";
import { atriumStatus as n } from "@refactor-circus/atrium";
import { UBadge as r, UButton as i, UCard as a, UCheckbox as o, UForm as s, UFormField as c, UTextarea as l } from "@refactor-circus/atrium/ui";
import { Fragment as u, createBlock as d, createCommentVNode as f, createElementBlock as p, createElementVNode as m, createVNode as h, defineComponent as g, isRef as _, openBlock as v, ref as y, renderList as b, shallowRef as x, toDisplayString as S, unref as C, watch as w, withCtx as T } from "vue";
import { useI18n as E } from "vue-i18n";
// src/components/CortexVersions.vue?vue&type=script&setup=true&lang.ts
var D = {
	class: "flex flex-col gap-5",
	"data-testid": "cortex-versions"
}, O = { class: "min-w-0" }, k = { class: "flex flex-wrap items-center gap-2" }, A = { class: "font-medium" }, ee = {
	key: 0,
	class: "mt-1 text-sm text-muted"
}, te = {
	class: "overflow-x-auto whitespace-pre-wrap rounded-md bg-elevated p-3 text-xs",
	"data-testid": "cortex-live"
}, j = { class: "font-medium" }, M = {
	key: 0,
	class: "text-sm text-muted"
}, N = {
	key: 1,
	class: "divide-y divide-default"
}, P = ["data-testid"], F = { class: "flex flex-wrap items-center gap-2" }, I = { class: "font-medium" }, L = {
	key: 1,
	class: "text-xs text-muted"
}, R = { class: "ml-auto flex items-center gap-2" }, z = {
	key: 0,
	class: "mt-2 overflow-x-auto whitespace-pre-wrap rounded-md bg-elevated p-3 text-xs"
}, B = { class: "font-medium" }, V = { key: 1 }, H = /* @__PURE__ */ g({
	__name: "CortexVersions",
	props: {
		api: {},
		live: {},
		liveTitle: {},
		published: {},
		override: { type: Boolean }
	},
	emits: ["changed"],
	setup(g, { emit: H }) {
		let U = g, W = H, { t: G } = E(), K = e(), q = x([]), J = y(!1), Y = y(null), X = y(U.live ?? ""), Z = y(!0);
		w(() => U.live, (e) => {
			X.value = e ?? "";
		});
		async function Q() {
			try {
				q.value = [...await U.api.list()].sort((e, t) => t.version - e.version), J.value = !1;
			} catch (e) {
				J.value = n(e) === 403, q.value = [];
			}
		}
		async function $(e) {
			e && (W("changed"), await Q());
		}
		async function ne() {
			await $(await K.run(() => U.api.store({
				content: X.value,
				publish: Z.value
			})));
		}
		async function re(e) {
			await $(await K.run(() => U.api.publish(e)));
		}
		async function ie() {
			let e = U.api.destroy;
			e && window.confirm(G("cortex.versions.confirm_remove")) && await $(await K.run(() => e()));
		}
		return Q(), (e, n) => {
			let y = r, x = a, w = i, E = l, H = c, U = o, W = s;
			return v(), p("div", D, [
				h(t, {
					forbidden: C(K).forbidden.value,
					message: C(K).message.value
				}, null, 8, ["forbidden", "message"]),
				h(x, null, {
					header: T(() => [m("div", O, [m("div", k, [m("h3", A, S(g.liveTitle), 1), g.override ? (v(), p(u, { key: 0 }, [g.published === null ? (v(), d(y, {
						key: 1,
						color: "neutral",
						variant: "subtle",
						label: C(G)("cortex.versions.from_code"),
						"data-status": "from_code"
					}, null, 8, ["label"])) : (v(), d(y, {
						key: 0,
						color: "success",
						variant: "subtle",
						label: C(G)("cortex.versions.overridden", { version: g.published }),
						"data-status": "overridden"
					}, null, 8, ["label"]))], 64)) : f("", !0)]), g.override ? (v(), p("p", ee, S(g.published === null ? C(G)("cortex.versions.no_override_hint") : C(G)("cortex.versions.override_hint")), 1)) : f("", !0)])]),
					default: T(() => [m("pre", te, S(g.live ?? ""), 1)]),
					_: 1
				}),
				C(J) ? f("", !0) : (v(), d(x, {
					key: 0,
					"data-testid": "cortex-versions-card"
				}, {
					header: T(() => [m("h3", j, S(C(G)("cortex.versions.title")), 1)]),
					default: T(() => [C(q).length === 0 ? (v(), p("p", M, S(C(G)("cortex.versions.none")), 1)) : (v(), p("ul", N, [(v(!0), p(u, null, b(C(q), (e) => (v(), p("li", {
						key: e.version,
						class: "py-2",
						"data-testid": `cortex-version-${e.version}`
					}, [m("div", F, [
						m("span", I, "v" + S(e.version), 1),
						e.version === g.published ? (v(), d(y, {
							key: 0,
							color: "success",
							variant: "subtle",
							label: C(G)("cortex.versions.published"),
							"data-status": "published"
						}, null, 8, ["label"])) : f("", !0),
						e.created_at ? (v(), p("span", L, S(e.created_at), 1)) : f("", !0),
						m("div", R, [h(w, {
							size: "xs",
							color: "neutral",
							variant: "ghost",
							icon: C(Y) === e.version ? "i-lucide-eye-off" : "i-lucide-eye",
							label: C(Y) === e.version ? C(G)("cortex.versions.hide") : C(G)("cortex.versions.view"),
							"data-testid": `cortex-view-${e.version}`,
							onClick: () => {
								Y.value = C(Y) === e.version ? null : e.version;
							}
						}, null, 8, [
							"icon",
							"label",
							"data-testid",
							"onClick"
						]), e.version === g.published ? f("", !0) : (v(), d(w, {
							key: 0,
							size: "xs",
							variant: "outline",
							icon: "i-lucide-badge-check",
							label: C(G)("cortex.versions.publish"),
							loading: C(K).busy.value,
							"data-testid": `cortex-publish-${e.version}`,
							onClick: (t) => re(e.version)
						}, null, 8, [
							"label",
							"loading",
							"data-testid",
							"onClick"
						]))])
					]), C(Y) === e.version ? (v(), p("pre", z, S(e.content), 1)) : f("", !0)], 8, P))), 128))]))]),
					_: 1
				})),
				h(x, { "data-testid": "cortex-new-version" }, {
					header: T(() => [m("h3", B, S(C(G)("cortex.versions.new")), 1)]),
					default: T(() => [h(W, {
						class: "flex flex-col gap-4",
						onSubmit: ne
					}, {
						default: T(() => [
							h(H, {
								label: C(G)("cortex.versions.content"),
								error: C(K).error("content"),
								required: ""
							}, {
								default: T(() => [h(E, {
									modelValue: C(X),
									"onUpdate:modelValue": n[0] ||= (e) => _(X) ? X.value = e : null,
									rows: 8,
									class: "w-full font-mono",
									"data-testid": "cortex-version-content"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							h(U, {
								modelValue: C(Z),
								"onUpdate:modelValue": n[1] ||= (e) => _(Z) ? Z.value = e : null,
								label: C(G)("cortex.versions.publish_now"),
								"data-testid": "cortex-version-publish"
							}, null, 8, ["modelValue", "label"]),
							m("div", null, [h(w, {
								type: "submit",
								icon: "i-lucide-file-plus",
								label: C(G)("cortex.versions.add"),
								loading: C(K).busy.value,
								"data-testid": "cortex-add-version"
							}, null, 8, ["label", "loading"])])
						]),
						_: 1
					})]),
					_: 1
				}),
				g.override && g.api.destroy && g.published !== null ? (v(), p("div", V, [h(w, {
					color: "error",
					variant: "outline",
					icon: "i-lucide-trash",
					label: C(G)("cortex.versions.remove"),
					"data-testid": "cortex-remove-override",
					onClick: ie
				}, null, 8, ["label"])])) : f("", !0)
			]);
		};
	}
});
export { H as t };
