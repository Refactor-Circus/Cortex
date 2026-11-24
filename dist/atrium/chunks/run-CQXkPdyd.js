import { a as e, i as t, t as n } from "./cortex-Bp7gHDgF.js";
import { t as r } from "./CortexActionError-BnFnixGr.js";
import { t as i } from "./CortexLoad-Bz-c2t3K.js";
import { t as a } from "./CortexPage-CtJD4yvP.js";
import { UButton as o, UCard as s, UEmpty as c, UForm as l, UFormField as u, USelect as d, UTextarea as f } from "@refactor-circus/atrium/ui";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createVNode as y, defineComponent as b, isRef as x, openBlock as S, ref as C, renderList as w, shallowRef as T, toDisplayString as E, unref as D, withCtx as O } from "vue";
import { useI18n as k } from "vue-i18n";
import { useRoute as A } from "vue-router";
// src/pages/run.vue?vue&type=script&setup=true&lang.ts
var j = { class: "flex max-w-2xl flex-col gap-5" }, M = { class: "font-medium" }, N = {
	class: "overflow-x-auto whitespace-pre-wrap rounded-md bg-elevated p-3 text-xs",
	"data-testid": "cortex-run-text"
}, P = {
	key: 0,
	class: "mt-4"
}, F = { class: "mb-2 text-sm font-semibold" }, I = {
	class: "divide-y divide-default text-sm",
	"data-testid": "cortex-run-usage"
}, L = { class: "tabular-nums" }, R = /* @__PURE__ */ b({
	__name: "run",
	setup(b) {
		let { t: R } = k(), z = A(), B = t(), { client: V, data: H, pending: U, failed: W, status: G } = e(async (e) => n(await e.GET("/runnable-agents")).data), K = m(() => H.value ?? []);
		function q(e) {
			return `${e.type === "concrete_agent" ? "concrete" : "virtual"}:${e.key}`;
		}
		let J = m(() => K.value.map((e) => ({
			label: e.name,
			value: q(e)
		}))), Y = C(typeof z.query.agent == "string" ? z.query.agent : ""), X = C(""), Z = T(null), Q = m(() => {
			let e = Z.value?.usage;
			return e && typeof e == "object" ? Object.entries(e).map(([e, t]) => ({
				key: e,
				value: typeof t == "object" ? JSON.stringify(t) : String(t)
			})) : [];
		});
		async function $() {
			let [e, t] = Y.value.split(/:(.*)/s);
			V && t && (Z.value = null, await B.run(async () => {
				let r = e === "concrete" ? await V.POST("/concrete-agents/{agent}/run", {
					params: { path: { agent: t } },
					body: { input: X.value }
				}) : await V.POST("/virtual-agents/{agent}/run", {
					params: { path: { agent: t } },
					body: { input: X.value }
				});
				Z.value = n(r).data;
			}));
		}
		return (e, t) => {
			let n = c, m = d, b = u, C = f, T = o, k = l, A = s;
			return S(), h(a, {
				title: D(R)("cortex.run.title"),
				"screen-key": "cortex.run"
			}, {
				default: O(() => [y(i, {
					ready: !D(U),
					failed: D(W),
					status: D(G)
				}, {
					default: O(() => [v("div", j, [D(K).length === 0 ? (S(), h(n, {
						key: 0,
						icon: "i-lucide-bot",
						title: D(R)("cortex.run.none"),
						variant: "naked",
						role: "status",
						"data-testid": "cortex-no-runnable-agents"
					}, null, 8, ["title"])) : (S(), h(A, { key: 1 }, {
						default: O(() => [y(k, {
							class: "flex flex-col gap-4",
							"data-testid": "cortex-run-form",
							onSubmit: $
						}, {
							default: O(() => [
								y(r, {
									forbidden: D(B).forbidden.value,
									message: D(B).message.value
								}, null, 8, ["forbidden", "message"]),
								y(b, {
									label: D(R)("cortex.run.agent"),
									error: D(B).error("agent"),
									required: ""
								}, {
									default: O(() => [y(m, {
										modelValue: D(Y),
										"onUpdate:modelValue": t[0] ||= (e) => x(Y) ? Y.value = e : null,
										items: D(J),
										placeholder: D(R)("cortex.run.select_agent"),
										class: "w-full",
										"data-testid": "cortex-run-agent-select"
									}, null, 8, [
										"modelValue",
										"items",
										"placeholder"
									])]),
									_: 1
								}, 8, ["label", "error"]),
								y(b, {
									label: D(R)("cortex.run.input"),
									error: D(B).error("input"),
									required: ""
								}, {
									default: O(() => [y(C, {
										modelValue: D(X),
										"onUpdate:modelValue": t[1] ||= (e) => x(X) ? X.value = e : null,
										rows: 6,
										class: "w-full",
										"data-testid": "cortex-run-input"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								v("div", null, [y(T, {
									type: "submit",
									icon: "i-lucide-play",
									label: D(R)("cortex.run.run"),
									loading: D(B).busy.value,
									disabled: D(Y) === "",
									"data-testid": "cortex-run"
								}, null, 8, [
									"label",
									"loading",
									"disabled"
								])])
							]),
							_: 1
						})]),
						_: 1
					})), D(Z) ? (S(), h(A, {
						key: 2,
						"data-testid": "cortex-run-result"
					}, {
						header: O(() => [v("h3", M, E(D(R)("cortex.run.response")), 1)]),
						default: O(() => [v("pre", N, E(D(Z).text), 1), D(Q).length ? (S(), _("div", P, [v("h4", F, E(D(R)("cortex.run.usage")), 1), v("dl", I, [(S(!0), _(p, null, w(D(Q), (e) => (S(), _("div", {
							key: e.key,
							class: "flex justify-between gap-4 py-1"
						}, [v("dt", null, E(e.key), 1), v("dd", L, E(e.value), 1)]))), 128))])])) : g("", !0)]),
						_: 1
					})) : g("", !0)])]),
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
export { R as default };
