import { i as e, n as t, r as n, t as r } from "./cortex-Bp7gHDgF.js";
import { t as i } from "./CortexActionError-BnFnixGr.js";
import { t as ee } from "./CortexToolPicker-D0avoGeq.js";
import { AtriumSelect as a } from "@refactor-circus/atrium";
import { UButton as o, UCard as s, UCheckbox as c, UForm as l, UFormField as u, UInput as d, UTextarea as te } from "@refactor-circus/atrium/ui";
import { Fragment as f, computed as p, createBlock as m, createCommentVNode as h, createElementBlock as g, createElementVNode as _, createVNode as v, defineComponent as y, openBlock as b, reactive as x, renderList as S, shallowRef as C, toDisplayString as w, unref as T, watch as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
// src/components/CortexAgentForm.vue?vue&type=script&setup=true&lang.ts
var k = { class: "grid gap-4 sm:grid-cols-2" }, A = { class: "flex flex-col gap-3" }, j = { class: "mb-2 text-sm font-medium" }, M = { class: "grid gap-3 sm:grid-cols-2" }, N = { class: "flex flex-col gap-2" }, ne = { class: "flex flex-col gap-2" }, re = { class: "flex items-center gap-2" }, P = /* @__PURE__ */ y({
	__name: "CortexAgentForm",
	props: { agent: {} },
	emits: ["saved"],
	setup(y, { emit: P }) {
		let F = y, I = P, L = [
			"temperature",
			"max_steps",
			"max_tokens",
			"top_p"
		], { t: R } = O(), z = n(), B = e(), V = C([]), H = C([]), U = C([]), W = C([]);
		function G() {
			let e = F.agent?.settings ?? {};
			return {
				name: F.agent?.name ?? "",
				slug: "",
				description: F.agent?.description ?? "",
				instructions: F.agent?.instructions ?? "",
				provider: F.agent?.provider ?? "",
				model: F.agent?.model ?? "",
				settings: Object.fromEntries(L.map((t) => [t, e[t] === void 0 || e[t] === null ? "" : String(e[t])])),
				tools: [...F.agent?.tools ?? []],
				sub_agents: [...F.agent?.sub_agents ?? []],
				concrete_sub_agents: [...F.agent?.concrete_sub_agents ?? []]
			};
		}
		let K = x(G());
		E(() => F.agent, () => Object.assign(K, G()));
		let q = p(() => {
			let e = V.value.map((e) => e.name);
			return K.provider && !e.includes(K.provider) ? [K.provider, ...e] : e;
		}), J = p(() => [.../* @__PURE__ */ new Set([...V.value.flatMap((e) => e.models), ...F.agent?.model ? [F.agent.model] : []])]), Y = p(() => H.value.map((e) => ({
			name: e.name,
			label: e.name,
			tags: e.tags
		}))), X = p(() => U.value.filter((e) => e.slug !== F.agent?.slug));
		function Z(e) {
			return e.map((e) => ({
				label: e,
				value: e
			}));
		}
		function ie(e) {
			K.provider = typeof e == "string" ? e : "", ae();
		}
		function Q(e, t, n) {
			let r = e.indexOf(t);
			n === !0 && r === -1 ? e.push(t) : n !== !0 && r !== -1 && e.splice(r, 1);
		}
		function ae() {
			let e = V.value.find((e) => e.name === K.provider)?.default_model;
			e && !K.model && (K.model = e);
		}
		async function oe() {
			if (!z) return;
			let [e, t, n, i] = await Promise.allSettled([
				z.GET("/providers").then((e) => r(e).data),
				z.GET("/tools").then((e) => r(e).data),
				z.GET("/virtual-agents", { params: { query: { per_page: 100 } } }).then((e) => r(e).data),
				z.GET("/concrete-agents").then((e) => r(e).data)
			]);
			V.value = e.status === "fulfilled" ? e.value : [], H.value = t.status === "fulfilled" ? t.value : [], U.value = n.status === "fulfilled" ? n.value : [], W.value = i.status === "fulfilled" ? i.value : [];
		}
		function $() {
			let e = Object.fromEntries(L.filter((e) => String(K.settings[e] ?? "").trim() !== "").map((e) => [e, Number(K.settings[e])]));
			return {
				name: K.name,
				...F.agent ? {} : { slug: K.slug },
				description: K.description === "" ? null : K.description,
				instructions: K.instructions,
				provider: K.provider === "" ? null : K.provider,
				model: K.model === "" ? null : K.model,
				settings: Object.keys(e).length ? e : null,
				tools: K.tools,
				sub_agents: K.sub_agents,
				concrete_sub_agents: K.concrete_sub_agents
			};
		}
		async function se() {
			if (!z) return;
			let e = null, n = F.agent;
			await B.run(async () => {
				let i = n ? await z.PATCH("/virtual-agents/{agent}", {
					params: { path: { agent: n.slug } },
					body: t($())
				}) : await z.POST("/virtual-agents", { body: t($()) });
				e = r(i).data;
			}) && e && I("saved", e);
		}
		return oe(), (e, t) => {
			let n = d, r = u, p = te, x = a, C = c, E = o, O = l, P = s;
			return b(), m(P, null, {
				default: D(() => [v(O, {
					class: "flex flex-col gap-4",
					"data-testid": "cortex-agent-form",
					onSubmit: se
				}, {
					default: D(() => [
						v(i, {
							forbidden: T(B).forbidden.value,
							message: T(B).message.value
						}, null, 8, ["forbidden", "message"]),
						v(r, {
							label: T(R)("cortex.agent.name"),
							error: T(B).error("name"),
							required: ""
						}, {
							default: D(() => [v(n, {
								modelValue: T(K).name,
								"onUpdate:modelValue": t[0] ||= (e) => T(K).name = e,
								class: "w-full",
								"data-testid": "cortex-agent-name"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						y.agent ? h("", !0) : (b(), m(r, {
							key: 0,
							label: T(R)("cortex.agent.slug"),
							hint: T(R)("cortex.agent.slug_hint"),
							error: T(B).error("slug"),
							required: ""
						}, {
							default: D(() => [v(n, {
								modelValue: T(K).slug,
								"onUpdate:modelValue": t[1] ||= (e) => T(K).slug = e,
								class: "w-full",
								"data-testid": "cortex-agent-slug"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						])),
						v(r, {
							label: T(R)("cortex.agent.description"),
							error: T(B).error("description")
						}, {
							default: D(() => [v(p, {
								modelValue: T(K).description,
								"onUpdate:modelValue": t[2] ||= (e) => T(K).description = e,
								class: "w-full",
								"data-testid": "cortex-agent-description"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						v(r, {
							label: T(R)("cortex.agent.prompt"),
							help: T(R)("cortex.agent.prompt_hint"),
							error: T(B).error("instructions"),
							required: ""
						}, {
							default: D(() => [v(p, {
								modelValue: T(K).instructions,
								"onUpdate:modelValue": t[3] ||= (e) => T(K).instructions = e,
								rows: 10,
								class: "w-full font-mono",
								"data-testid": "cortex-agent-instructions"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"help",
							"error"
						]),
						_("div", k, [v(r, {
							label: T(R)("cortex.agent.provider"),
							error: T(B).error("provider")
						}, {
							default: D(() => [v(x, {
								"model-value": T(K).provider,
								items: Z(T(q)),
								blank: T(R)("cortex.agent.provider_default"),
								class: "w-full",
								"data-testid": "cortex-agent-provider",
								"onUpdate:modelValue": ie
							}, null, 8, [
								"model-value",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label", "error"]), v(r, {
							label: T(R)("cortex.agent.model"),
							error: T(B).error("model")
						}, {
							default: D(() => [v(x, {
								modelValue: T(K).model,
								"onUpdate:modelValue": t[4] ||= (e) => T(K).model = e,
								items: Z(T(J)),
								blank: T(R)("cortex.agent.provider_default"),
								class: "w-full",
								"data-testid": "cortex-agent-model"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label", "error"])]),
						_("fieldset", A, [_("legend", j, w(T(R)("cortex.agent.settings")), 1), _("div", M, [
							v(r, {
								label: T(R)("cortex.agent.temperature"),
								error: T(B).error("settings.temperature")
							}, {
								default: D(() => [v(n, {
									modelValue: T(K).settings.temperature,
									"onUpdate:modelValue": t[5] ||= (e) => T(K).settings.temperature = e,
									type: "number",
									step: "0.1",
									min: "0",
									max: "2",
									class: "w-full",
									"data-testid": "cortex-agent-temperature"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							v(r, {
								label: T(R)("cortex.agent.max_steps"),
								error: T(B).error("settings.max_steps")
							}, {
								default: D(() => [v(n, {
									modelValue: T(K).settings.max_steps,
									"onUpdate:modelValue": t[6] ||= (e) => T(K).settings.max_steps = e,
									type: "number",
									min: "1",
									class: "w-full",
									"data-testid": "cortex-agent-max-steps"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							v(r, {
								label: T(R)("cortex.agent.max_tokens"),
								error: T(B).error("settings.max_tokens")
							}, {
								default: D(() => [v(n, {
									modelValue: T(K).settings.max_tokens,
									"onUpdate:modelValue": t[7] ||= (e) => T(K).settings.max_tokens = e,
									type: "number",
									min: "1",
									class: "w-full",
									"data-testid": "cortex-agent-max-tokens"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							v(r, {
								label: T(R)("cortex.agent.top_p"),
								error: T(B).error("settings.top_p")
							}, {
								default: D(() => [v(n, {
									modelValue: T(K).settings.top_p,
									"onUpdate:modelValue": t[8] ||= (e) => T(K).settings.top_p = e,
									type: "number",
									step: "0.05",
									min: "0",
									max: "1",
									class: "w-full",
									"data-testid": "cortex-agent-top-p"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"])
						])]),
						T(Y).length ? (b(), m(r, {
							key: 1,
							label: T(R)("cortex.agent.tools"),
							error: T(B).error("tools")
						}, {
							default: D(() => [v(ee, {
								modelValue: T(K).tools,
								"onUpdate:modelValue": t[9] ||= (e) => T(K).tools = e,
								tools: T(Y),
								"id-prefix": "cortex-agent-tool-"
							}, null, 8, ["modelValue", "tools"])]),
							_: 1
						}, 8, ["label", "error"])) : h("", !0),
						T(X).length ? (b(), m(r, {
							key: 2,
							label: T(R)("cortex.agent.sub_agents"),
							error: T(B).error("sub_agents")
						}, {
							default: D(() => [_("div", N, [(b(!0), g(f, null, S(T(X), (e) => (b(), m(C, {
								key: e.slug,
								"model-value": T(K).sub_agents.includes(e.slug),
								label: e.name,
								"data-testid": `cortex-sub-agent-${e.slug}`,
								"onUpdate:modelValue": (t) => Q(T(K).sub_agents, e.slug, t)
							}, null, 8, [
								"model-value",
								"label",
								"data-testid",
								"onUpdate:modelValue"
							]))), 128))])]),
							_: 1
						}, 8, ["label", "error"])) : h("", !0),
						T(W).length ? (b(), m(r, {
							key: 3,
							label: T(R)("cortex.agent.concrete_sub_agents"),
							error: T(B).error("concrete_sub_agents")
						}, {
							default: D(() => [_("div", ne, [(b(!0), g(f, null, S(T(W), (e) => (b(), m(C, {
								key: e.name,
								"model-value": T(K).concrete_sub_agents.includes(e.name),
								label: e.name,
								"data-testid": `cortex-concrete-sub-agent-${e.name}`,
								"onUpdate:modelValue": (t) => Q(T(K).concrete_sub_agents, e.name, t)
							}, null, 8, [
								"model-value",
								"label",
								"data-testid",
								"onUpdate:modelValue"
							]))), 128))])]),
							_: 1
						}, 8, ["label", "error"])) : h("", !0),
						_("div", re, [v(E, {
							type: "submit",
							icon: "i-lucide-check",
							label: T(R)("cortex.agent.save"),
							loading: T(B).busy.value,
							"data-testid": "cortex-save-agent"
						}, null, 8, ["label", "loading"]), v(E, {
							to: "/cortex/virtual-agents",
							color: "neutral",
							variant: "ghost",
							label: T(R)("cortex.agent.cancel")
						}, null, 8, ["label"])])
					]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { P as t };
