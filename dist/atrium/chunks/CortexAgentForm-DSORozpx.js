import { i as e, n as t, r as n, t as r } from "./cortex-Bp7gHDgF.js";
import { t as i } from "./CortexActionError-BnFnixGr.js";
import { t as a } from "./CortexToolPicker-D0avoGeq.js";
import { UButton as ee, UCard as te, UCheckbox as o, UForm as s, UFormField as c, UInput as l, USelect as u, UTextarea as ne } from "@refactor-circus/atrium/ui";
import { Fragment as d, computed as f, createBlock as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createVNode as _, defineComponent as v, isRef as y, openBlock as b, reactive as x, renderList as S, shallowRef as C, toDisplayString as re, unref as w, watch as T, withCtx as E } from "vue";
import { useI18n as D } from "vue-i18n";
// src/components/CortexAgentForm.vue?vue&type=script&setup=true&lang.ts
var ie = { class: "grid gap-4 sm:grid-cols-2" }, O = { class: "flex flex-col gap-3" }, k = { class: "mb-2 text-sm font-medium" }, A = { class: "grid gap-3 sm:grid-cols-2" }, j = { class: "flex flex-col gap-2" }, M = { class: "flex flex-col gap-2" }, ae = { class: "flex items-center gap-2" }, N = "@default", P = /* @__PURE__ */ v({
	__name: "CortexAgentForm",
	props: { agent: {} },
	emits: ["saved"],
	setup(v, { emit: P }) {
		let F = v, oe = P, I = [
			"temperature",
			"max_steps",
			"max_tokens",
			"top_p"
		], { t: L } = D(), R = n(), z = e(), B = C([]), V = C([]), H = C([]), U = C([]);
		function W() {
			let e = F.agent?.settings ?? {};
			return {
				name: F.agent?.name ?? "",
				slug: "",
				description: F.agent?.description ?? "",
				instructions: F.agent?.instructions ?? "",
				provider: F.agent?.provider ?? "",
				model: F.agent?.model ?? "",
				settings: Object.fromEntries(I.map((t) => [t, e[t] === void 0 || e[t] === null ? "" : String(e[t])])),
				tools: [...F.agent?.tools ?? []],
				sub_agents: [...F.agent?.sub_agents ?? []],
				concrete_sub_agents: [...F.agent?.concrete_sub_agents ?? []]
			};
		}
		let G = x(W());
		T(() => F.agent, () => Object.assign(G, W()));
		let K = f(() => {
			let e = B.value.map((e) => e.name);
			return G.provider && !e.includes(G.provider) ? [G.provider, ...e] : e;
		}), se = f(() => [.../* @__PURE__ */ new Set([...B.value.flatMap((e) => e.models), ...F.agent?.model ? [F.agent.model] : []])]), q = f(() => V.value.map((e) => ({
			name: e.name,
			label: e.name,
			tags: e.tags
		}))), J = f(() => H.value.filter((e) => e.slug !== F.agent?.slug));
		function Y(e) {
			return [{
				label: L("cortex.agent.provider_default"),
				value: N
			}, ...e.map((e) => ({
				label: e,
				value: e
			}))];
		}
		let X = f({
			get: () => G.provider === "" ? N : G.provider,
			set: (e) => {
				G.provider = e === N ? "" : e, ce();
			}
		}), Z = f({
			get: () => G.model === "" ? N : G.model,
			set: (e) => {
				G.model = e === N ? "" : e;
			}
		});
		function Q(e, t, n) {
			let r = e.indexOf(t);
			n === !0 && r === -1 ? e.push(t) : n !== !0 && r !== -1 && e.splice(r, 1);
		}
		function ce() {
			let e = B.value.find((e) => e.name === G.provider)?.default_model;
			e && !G.model && (G.model = e);
		}
		async function le() {
			if (!R) return;
			let [e, t, n, i] = await Promise.allSettled([
				R.GET("/providers").then((e) => r(e).data),
				R.GET("/tools").then((e) => r(e).data),
				R.GET("/virtual-agents", { params: { query: { per_page: 100 } } }).then((e) => r(e).data),
				R.GET("/concrete-agents").then((e) => r(e).data)
			]);
			B.value = e.status === "fulfilled" ? e.value : [], V.value = t.status === "fulfilled" ? t.value : [], H.value = n.status === "fulfilled" ? n.value : [], U.value = i.status === "fulfilled" ? i.value : [];
		}
		function $() {
			let e = Object.fromEntries(I.filter((e) => String(G.settings[e] ?? "").trim() !== "").map((e) => [e, Number(G.settings[e])]));
			return {
				name: G.name,
				...F.agent ? {} : { slug: G.slug },
				description: G.description === "" ? null : G.description,
				instructions: G.instructions,
				provider: G.provider === "" ? null : G.provider,
				model: G.model === "" ? null : G.model,
				settings: Object.keys(e).length ? e : null,
				tools: G.tools,
				sub_agents: G.sub_agents,
				concrete_sub_agents: G.concrete_sub_agents
			};
		}
		async function ue() {
			if (!R) return;
			let e = null, n = F.agent;
			await z.run(async () => {
				let i = n ? await R.PATCH("/virtual-agents/{agent}", {
					params: { path: { agent: n.slug } },
					body: t($())
				}) : await R.POST("/virtual-agents", { body: t($()) });
				e = r(i).data;
			}) && e && oe("saved", e);
		}
		return le(), (e, t) => {
			let n = l, r = c, f = ne, x = u, C = o, T = ee, D = s, N = te;
			return b(), p(N, null, {
				default: E(() => [_(D, {
					class: "flex flex-col gap-4",
					"data-testid": "cortex-agent-form",
					onSubmit: ue
				}, {
					default: E(() => [
						_(i, {
							forbidden: w(z).forbidden.value,
							message: w(z).message.value
						}, null, 8, ["forbidden", "message"]),
						_(r, {
							label: w(L)("cortex.agent.name"),
							error: w(z).error("name"),
							required: ""
						}, {
							default: E(() => [_(n, {
								modelValue: w(G).name,
								"onUpdate:modelValue": t[0] ||= (e) => w(G).name = e,
								class: "w-full",
								"data-testid": "cortex-agent-name"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						v.agent ? m("", !0) : (b(), p(r, {
							key: 0,
							label: w(L)("cortex.agent.slug"),
							hint: w(L)("cortex.agent.slug_hint"),
							error: w(z).error("slug"),
							required: ""
						}, {
							default: E(() => [_(n, {
								modelValue: w(G).slug,
								"onUpdate:modelValue": t[1] ||= (e) => w(G).slug = e,
								class: "w-full",
								"data-testid": "cortex-agent-slug"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						])),
						_(r, {
							label: w(L)("cortex.agent.description"),
							error: w(z).error("description")
						}, {
							default: E(() => [_(f, {
								modelValue: w(G).description,
								"onUpdate:modelValue": t[2] ||= (e) => w(G).description = e,
								class: "w-full",
								"data-testid": "cortex-agent-description"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						_(r, {
							label: w(L)("cortex.agent.prompt"),
							help: w(L)("cortex.agent.prompt_hint"),
							error: w(z).error("instructions"),
							required: ""
						}, {
							default: E(() => [_(f, {
								modelValue: w(G).instructions,
								"onUpdate:modelValue": t[3] ||= (e) => w(G).instructions = e,
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
						g("div", ie, [_(r, {
							label: w(L)("cortex.agent.provider"),
							error: w(z).error("provider")
						}, {
							default: E(() => [_(x, {
								modelValue: w(X),
								"onUpdate:modelValue": t[4] ||= (e) => y(X) ? X.value = e : null,
								items: Y(w(K)),
								class: "w-full",
								"data-testid": "cortex-agent-provider"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label", "error"]), _(r, {
							label: w(L)("cortex.agent.model"),
							error: w(z).error("model")
						}, {
							default: E(() => [_(x, {
								modelValue: w(Z),
								"onUpdate:modelValue": t[5] ||= (e) => y(Z) ? Z.value = e : null,
								items: Y(w(se)),
								class: "w-full",
								"data-testid": "cortex-agent-model"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label", "error"])]),
						g("fieldset", O, [g("legend", k, re(w(L)("cortex.agent.settings")), 1), g("div", A, [
							_(r, {
								label: w(L)("cortex.agent.temperature"),
								error: w(z).error("settings.temperature")
							}, {
								default: E(() => [_(n, {
									modelValue: w(G).settings.temperature,
									"onUpdate:modelValue": t[6] ||= (e) => w(G).settings.temperature = e,
									type: "number",
									step: "0.1",
									min: "0",
									max: "2",
									class: "w-full",
									"data-testid": "cortex-agent-temperature"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							_(r, {
								label: w(L)("cortex.agent.max_steps"),
								error: w(z).error("settings.max_steps")
							}, {
								default: E(() => [_(n, {
									modelValue: w(G).settings.max_steps,
									"onUpdate:modelValue": t[7] ||= (e) => w(G).settings.max_steps = e,
									type: "number",
									min: "1",
									class: "w-full",
									"data-testid": "cortex-agent-max-steps"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							_(r, {
								label: w(L)("cortex.agent.max_tokens"),
								error: w(z).error("settings.max_tokens")
							}, {
								default: E(() => [_(n, {
									modelValue: w(G).settings.max_tokens,
									"onUpdate:modelValue": t[8] ||= (e) => w(G).settings.max_tokens = e,
									type: "number",
									min: "1",
									class: "w-full",
									"data-testid": "cortex-agent-max-tokens"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							_(r, {
								label: w(L)("cortex.agent.top_p"),
								error: w(z).error("settings.top_p")
							}, {
								default: E(() => [_(n, {
									modelValue: w(G).settings.top_p,
									"onUpdate:modelValue": t[9] ||= (e) => w(G).settings.top_p = e,
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
						w(q).length ? (b(), p(r, {
							key: 1,
							label: w(L)("cortex.agent.tools"),
							error: w(z).error("tools")
						}, {
							default: E(() => [_(a, {
								modelValue: w(G).tools,
								"onUpdate:modelValue": t[10] ||= (e) => w(G).tools = e,
								tools: w(q),
								"id-prefix": "cortex-agent-tool-"
							}, null, 8, ["modelValue", "tools"])]),
							_: 1
						}, 8, ["label", "error"])) : m("", !0),
						w(J).length ? (b(), p(r, {
							key: 2,
							label: w(L)("cortex.agent.sub_agents"),
							error: w(z).error("sub_agents")
						}, {
							default: E(() => [g("div", j, [(b(!0), h(d, null, S(w(J), (e) => (b(), p(C, {
								key: e.slug,
								"model-value": w(G).sub_agents.includes(e.slug),
								label: e.name,
								"data-testid": `cortex-sub-agent-${e.slug}`,
								"onUpdate:modelValue": (t) => Q(w(G).sub_agents, e.slug, t)
							}, null, 8, [
								"model-value",
								"label",
								"data-testid",
								"onUpdate:modelValue"
							]))), 128))])]),
							_: 1
						}, 8, ["label", "error"])) : m("", !0),
						w(U).length ? (b(), p(r, {
							key: 3,
							label: w(L)("cortex.agent.concrete_sub_agents"),
							error: w(z).error("concrete_sub_agents")
						}, {
							default: E(() => [g("div", M, [(b(!0), h(d, null, S(w(U), (e) => (b(), p(C, {
								key: e.name,
								"model-value": w(G).concrete_sub_agents.includes(e.name),
								label: e.name,
								"data-testid": `cortex-concrete-sub-agent-${e.name}`,
								"onUpdate:modelValue": (t) => Q(w(G).concrete_sub_agents, e.name, t)
							}, null, 8, [
								"model-value",
								"label",
								"data-testid",
								"onUpdate:modelValue"
							]))), 128))])]),
							_: 1
						}, 8, ["label", "error"])) : m("", !0),
						g("div", ae, [_(T, {
							type: "submit",
							icon: "i-lucide-check",
							label: w(L)("cortex.agent.save"),
							loading: w(z).busy.value,
							"data-testid": "cortex-save-agent"
						}, null, 8, ["label", "loading"]), _(T, {
							to: "/cortex/virtual-agents",
							color: "neutral",
							variant: "ghost",
							label: w(L)("cortex.agent.cancel")
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
