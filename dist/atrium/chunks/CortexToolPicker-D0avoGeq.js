import { UBadge as e, UButton as t, UCheckbox as n, UInput as r, USwitch as i } from "@refactor-circus/atrium/ui";
import { Fragment as a, computed as o, createBlock as s, createCommentVNode as c, createElementBlock as l, createElementVNode as u, createVNode as d, defineComponent as f, isRef as p, mergeModels as m, openBlock as h, ref as g, renderList as _, toDisplayString as v, unref as y, useModel as b } from "vue";
import { useI18n as x } from "vue-i18n";
// src/components/CortexToolPicker.vue?vue&type=script&setup=true&lang.ts
var S = {
	class: "flex flex-col gap-3",
	"data-testid": "cortex-tool-picker"
}, C = { class: "flex flex-wrap items-center gap-3" }, w = {
	class: "ml-auto text-xs text-muted",
	"data-testid": "cortex-selected-count"
}, T = {
	key: 0,
	class: "flex flex-wrap gap-2",
	"data-testid": "cortex-picker-tags"
}, E = { class: "flex max-h-80 flex-col gap-2 overflow-y-auto" }, D = /* @__PURE__ */ f({
	__name: "CortexToolPicker",
	props: /*@__PURE__*/ m({
		tools: {},
		idPrefix: {}
	}, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(f) {
		let m = f, D = b(f, "modelValue"), { t: O } = x(), k = g(null), A = g(""), j = g(!1), M = o(() => [...new Set(m.tools.flatMap((e) => e.tags))].sort());
		function N(e) {
			return (k.value === null || e.tags.includes(k.value)) && (A.value === "" || e.name.toLowerCase().includes(A.value.toLowerCase())) && (!j.value || D.value.includes(e.name));
		}
		let P = o(() => m.tools.filter(N));
		function F(e, t) {
			D.value = t === !0 ? [...D.value.filter((t) => t !== e), e] : D.value.filter((t) => t !== e);
		}
		return (o, m) => {
			let g = r, b = i, x = t, N = n, I = e;
			return h(), l("div", S, [
				u("div", C, [
					d(g, {
						modelValue: y(A),
						"onUpdate:modelValue": m[0] ||= (e) => p(A) ? A.value = e : null,
						icon: "i-lucide-search",
						placeholder: y(O)("cortex.tools.search"),
						"data-testid": "cortex-tool-search"
					}, null, 8, ["modelValue", "placeholder"]),
					d(b, {
						modelValue: y(j),
						"onUpdate:modelValue": m[1] ||= (e) => p(j) ? j.value = e : null,
						label: y(O)("cortex.tools.selected_only"),
						"data-testid": "cortex-selected-only"
					}, null, 8, ["modelValue", "label"]),
					u("span", w, v(y(O)("cortex.tools.selected", { count: D.value.length })), 1)
				]),
				y(M).length ? (h(), l("div", T, [d(x, {
					size: "xs",
					variant: y(k) === null ? "solid" : "outline",
					color: "neutral",
					label: y(O)("cortex.tools.all_tags"),
					onClick: m[2] ||= () => {
						k.value = null;
					}
				}, null, 8, ["variant", "label"]), (h(!0), l(a, null, _(y(M), (e) => (h(), s(x, {
					key: e,
					size: "xs",
					variant: y(k) === e ? "solid" : "outline",
					color: "neutral",
					label: e,
					"data-testid": `cortex-picker-tag-${e}`,
					onClick: () => {
						k.value = y(k) === e ? null : e;
					}
				}, null, 8, [
					"variant",
					"label",
					"data-testid",
					"onClick"
				]))), 128))])) : c("", !0),
				u("div", E, [(h(!0), l(a, null, _(y(P), (e) => (h(), l("div", {
					key: e.name,
					class: "flex flex-wrap items-center gap-2"
				}, [d(N, {
					"model-value": D.value.includes(e.name),
					label: e.label,
					"data-testid": `${f.idPrefix ?? "cortex-tool-"}${e.name}`,
					"onUpdate:modelValue": (t) => F(e.name, t)
				}, null, 8, [
					"model-value",
					"label",
					"data-testid",
					"onUpdate:modelValue"
				]), (h(!0), l(a, null, _(e.tags, (e) => (h(), s(I, {
					key: e,
					color: "neutral",
					variant: "subtle",
					size: "sm",
					label: e
				}, null, 8, ["label"]))), 128))]))), 128))])
			]);
		};
	}
});
export { D as t };
