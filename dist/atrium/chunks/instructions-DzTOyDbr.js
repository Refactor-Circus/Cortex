import { a as e, i as t, n, t as r } from "./cortex-Bp7gHDgF.js";
import { t as i } from "./CortexActionError-BnFnixGr.js";
import { t as a } from "./CortexLoad-Bz-c2t3K.js";
import { t as o } from "./CortexPage-CtJD4yvP.js";
import { t as s } from "./CortexToolPicker-D0avoGeq.js";
import { t as c } from "./CortexVersions-K-zu41Hh.js";
import { UAlert as l, UBadge as u, UButton as d, UCard as f, UCheckbox as p } from "@refactor-circus/atrium/ui";
import { computed as m, createBlock as h, createCommentVNode as g, createElementVNode as _, createVNode as v, defineComponent as y, isRef as b, openBlock as x, ref as S, toDisplayString as C, unref as w, watch as T, withCtx as E } from "vue";
import { useI18n as D } from "vue-i18n";
import { useRoute as O } from "vue-router";
// src/pages/servers/[server]/instructions.vue?vue&type=script&setup=true&lang.ts
var k = { class: "flex flex-col gap-3" }, A = { class: "flex flex-wrap items-center gap-2" }, j = { class: "font-medium" }, M = { class: "flex flex-col gap-4" }, N = { class: "flex items-center gap-2" }, P = /* @__PURE__ */ y({
	__name: "instructions",
	setup(y) {
		let { t: P } = D(), F = O(), I = t(), L = m(() => String(F.params.server)), R = () => ({ params: { path: { server: L.value } } }), { client: z, data: B, pending: V, failed: H, status: U, reload: W } = e(async (e) => {
			let [t, n, i] = await Promise.all([
				e.GET("/servers/{server}/instructions", R()).then((e) => r(e).data),
				e.GET("/servers").then((e) => r(e).data),
				e.GET("/tools").then((e) => r(e).data).catch(() => [])
			]);
			return {
				instruction: t,
				server: n.find((e) => e.name === L.value) ?? null,
				registered: i
			};
		}), G = m(() => B.value?.server ?? null), K = m(() => B.value?.instruction.published_content ?? G.value?.instructions ?? null), q = S([]), J = S(!1), Y = S(!1);
		T(G, (e) => {
			q.value = [...e?.tools ?? []];
		}, { immediate: !0 });
		let X = m(() => {
			let e = G.value?.tools ?? [], t = B.value?.registered ?? [];
			return [...e.map((e) => ({
				name: e,
				label: e,
				tags: t.find((t) => t.name === e)?.tags ?? []
			})), ...t.filter((t) => !e.includes(t.name)).map((e) => ({
				name: e.name,
				label: e.name,
				tags: e.tags
			}))];
		}), Z = {
			list: async () => r(await z.GET("/servers/{server}/instructions/versions", R())).data,
			store: async (e) => r(await z.POST("/servers/{server}/instructions/versions", {
				...R(),
				body: e
			})),
			publish: async (e) => r(await z.POST("/servers/{server}/instructions/versions/{version}/publish", { params: { path: {
				server: L.value,
				version: e
			} } })),
			destroy: async () => r(await z.DELETE("/servers/{server}/instructions", R()))
		};
		async function Q() {
			Y.value = q.value.length === 0 && !J.value, z && !Y.value && await I.run(async () => r(await z.PUT("/servers/{server}/tools", {
				...R(),
				body: n({ tools: q.value })
			}))) && (J.value = !1, await W());
		}
		async function $() {
			z && window.confirm(P("cortex.tools.confirm_restore")) && await I.run(async () => r(await z.DELETE("/servers/{server}/tools", R()))) && await W();
		}
		return (e, t) => {
			let n = u, r = l, m = p, y = d, S = f;
			return x(), h(o, {
				title: w(P)("cortex.servers.instructions_title", { server: w(L) }),
				"screen-key": "cortex.server-instructions",
				record: w(L),
				back: {
					to: "/cortex/servers",
					label: w(P)("cortex.servers.title")
				}
			}, {
				default: E(() => [v(a, {
					ready: !w(V) && w(B) !== null,
					failed: w(H),
					status: w(U)
				}, {
					default: E(() => [_("div", k, [v(c, {
						api: Z,
						live: w(K),
						"live-title": w(P)("cortex.servers.instructions"),
						published: w(B)?.instruction.published_version ?? null,
						override: "",
						onChanged: w(W)
					}, null, 8, [
						"live",
						"live-title",
						"published",
						"onChanged"
					]), w(G) ? (x(), h(S, {
						key: 0,
						"data-testid": "cortex-server-tools"
					}, {
						header: E(() => [_("div", A, [_("h3", j, C(w(P)("cortex.agent.tools")), 1), w(G).tools_overridden ? (x(), h(n, {
							key: 0,
							color: "success",
							variant: "subtle",
							label: w(P)("cortex.tools.overridden"),
							"data-status": "overridden"
						}, null, 8, ["label"])) : (x(), h(n, {
							key: 1,
							color: "neutral",
							variant: "subtle",
							label: w(P)("cortex.versions.from_code"),
							"data-status": "from_code"
						}, null, 8, ["label"]))])]),
						default: E(() => [_("div", M, [
							v(i, {
								forbidden: w(I).forbidden.value,
								message: w(I).message.value
							}, null, 8, ["forbidden", "message"]),
							w(I).error("tools") ? (x(), h(r, {
								key: 0,
								color: "error",
								variant: "subtle",
								title: w(I).error("tools"),
								"data-testid": "cortex-tools-error"
							}, null, 8, ["title"])) : g("", !0),
							w(Y) ? (x(), h(r, {
								key: 1,
								color: "warning",
								variant: "subtle",
								title: w(P)("cortex.servers.confirm_empty_needed"),
								"data-testid": "cortex-confirm-empty-needed"
							}, null, 8, ["title"])) : g("", !0),
							v(s, {
								modelValue: w(q),
								"onUpdate:modelValue": t[0] ||= (e) => b(q) ? q.value = e : null,
								tools: w(X),
								"id-prefix": "cortex-server-tool-"
							}, null, 8, ["modelValue", "tools"]),
							v(m, {
								modelValue: w(J),
								"onUpdate:modelValue": t[1] ||= (e) => b(J) ? J.value = e : null,
								color: "error",
								label: w(P)("cortex.servers.confirm_empty"),
								"data-testid": "cortex-confirm-empty"
							}, null, 8, ["modelValue", "label"]),
							_("div", N, [v(y, {
								icon: "i-lucide-check",
								label: w(P)("cortex.tools.save"),
								loading: w(I).busy.value,
								"data-testid": "cortex-save-tools",
								onClick: Q
							}, null, 8, ["label", "loading"]), w(G).tools_overridden ? (x(), h(y, {
								key: 0,
								color: "neutral",
								variant: "ghost",
								icon: "i-lucide-undo-2",
								label: w(P)("cortex.tools.restore"),
								"data-testid": "cortex-restore-tools",
								onClick: $
							}, null, 8, ["label"])) : g("", !0)])
						])]),
						_: 1
					})) : g("", !0)])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, [
				"title",
				"record",
				"back"
			]);
		};
	}
});
export { P as default };
