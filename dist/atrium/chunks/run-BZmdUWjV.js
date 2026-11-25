import { a as e, i as t, t as n } from "./cortex-Bp7gHDgF.js";
import { t as r } from "./CortexActionError-BnFnixGr.js";
import { t as i } from "./CortexLoad-Bz-c2t3K.js";
import { t as a } from "./CortexPage-CtJD4yvP.js";
import { AtriumDetails as o } from "@refactor-circus/atrium";
import { UButton as s, UCard as c, UEmpty as l, UForm as u, UFormField as d, USelect as f, UTextarea as p } from "@refactor-circus/atrium/ui";
import { computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createVNode as y, defineComponent as b, isRef as x, openBlock as S, ref as C, shallowRef as w, toDisplayString as T, unref as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRoute as k } from "vue-router";
// src/pages/run.vue?vue&type=script&setup=true&lang.ts
var A = { class: "flex max-w-2xl flex-col gap-5" }, j = { class: "font-medium" }, M = {
	class: "overflow-x-auto whitespace-pre-wrap rounded-md bg-elevated p-3 text-xs",
	"data-testid": "cortex-run-text"
}, N = {
	key: 0,
	class: "mt-4"
}, P = { class: "mb-2 text-sm font-semibold" }, F = /* @__PURE__ */ b({
	__name: "run",
	setup(b) {
		let { t: F } = O(), I = k(), L = t(), { client: R, data: z, pending: B, failed: V, status: H } = e(async (e) => n(await e.GET("/runnable-agents")).data), U = m(() => z.value ?? []);
		function W(e) {
			return `${e.type === "concrete_agent" ? "concrete" : "virtual"}:${e.key}`;
		}
		let G = m(() => U.value.map((e) => ({
			label: e.name,
			value: W(e)
		}))), K = C(typeof I.query.agent == "string" ? I.query.agent : ""), q = C(""), J = w(null), Y = m(() => {
			let e = J.value?.usage;
			return e && typeof e == "object" ? Object.entries(e).map(([e, t]) => ({
				key: e,
				label: e,
				value: typeof t == "object" ? JSON.stringify(t) : String(t),
				mono: !0
			})) : [];
		});
		async function X() {
			let [e, t] = K.value.split(/:(.*)/s);
			R && t && (J.value = null, await L.run(async () => {
				let r = e === "concrete" ? await R.POST("/concrete-agents/{agent}/run", {
					params: { path: { agent: t } },
					body: { input: q.value }
				}) : await R.POST("/virtual-agents/{agent}/run", {
					params: { path: { agent: t } },
					body: { input: q.value }
				});
				J.value = n(r).data;
			}));
		}
		return (e, t) => {
			let n = l, m = f, b = d, C = p, w = s, O = u, k = c, I = o;
			return S(), h(a, {
				title: E(F)("cortex.run.title"),
				"screen-key": "cortex.run"
			}, {
				default: D(() => [y(i, {
					ready: !E(B),
					failed: E(V),
					status: E(H)
				}, {
					default: D(() => [v("div", A, [E(U).length === 0 ? (S(), h(n, {
						key: 0,
						icon: "i-lucide-bot",
						title: E(F)("cortex.run.none"),
						variant: "naked",
						role: "status",
						"data-testid": "cortex-no-runnable-agents"
					}, null, 8, ["title"])) : (S(), h(k, { key: 1 }, {
						default: D(() => [y(O, {
							class: "flex flex-col gap-4",
							"data-testid": "cortex-run-form",
							onSubmit: X
						}, {
							default: D(() => [
								y(r, {
									forbidden: E(L).forbidden.value,
									message: E(L).message.value
								}, null, 8, ["forbidden", "message"]),
								y(b, {
									label: E(F)("cortex.run.agent"),
									error: E(L).error("agent"),
									required: ""
								}, {
									default: D(() => [y(m, {
										modelValue: E(K),
										"onUpdate:modelValue": t[0] ||= (e) => x(K) ? K.value = e : null,
										items: E(G),
										placeholder: E(F)("cortex.run.select_agent"),
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
									label: E(F)("cortex.run.input"),
									error: E(L).error("input"),
									required: ""
								}, {
									default: D(() => [y(C, {
										modelValue: E(q),
										"onUpdate:modelValue": t[1] ||= (e) => x(q) ? q.value = e : null,
										rows: 6,
										class: "w-full",
										"data-testid": "cortex-run-input"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								v("div", null, [y(w, {
									type: "submit",
									icon: "i-lucide-play",
									label: E(F)("cortex.run.run"),
									loading: E(L).busy.value,
									disabled: E(K) === "",
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
					})), E(J) ? (S(), h(k, {
						key: 2,
						"data-testid": "cortex-run-result"
					}, {
						header: D(() => [v("h3", j, T(E(F)("cortex.run.response")), 1)]),
						default: D(() => [v("pre", M, T(E(J).text), 1), E(Y).length ? (S(), _("div", N, [v("h4", P, T(E(F)("cortex.run.usage")), 1), y(I, {
							items: E(Y),
							"data-testid": "cortex-run-usage"
						}, null, 8, ["items"])])) : g("", !0)]),
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
export { F as default };
