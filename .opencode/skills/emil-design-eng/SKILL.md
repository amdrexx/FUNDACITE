---
name: emil-design-eng
description: Encodes Emil Kowalski's philosophy on UI polish, component design, animation decisions, and the invisible details that make software feel great.
---

# Emil Kowalski - Design Engineering Skill

Use this skill when adding motion, refining micro-interactions, building UI components, or evaluating animation performance.

## 1. Decision Framework: Should this animate at all?

Before writing any animation code, evaluate frequency of use:
- **100+ times/day** (keyboard shortcuts, command palettes): **No animation. Ever.** (e.g., Raycast palette).
- **Tens of times/day** (hover effects, list navigation): Remove or drastically reduce duration (<= 150ms).
- **Occasional** (modals, drawers, toasts): Standard motion (200-300ms).
- **Rare / First-time** (onboarding, milestone celebration): Delighted, expressive motion allowed.

## 2. Animation & Timing Rules

- **Micro-interactions**: 150–250ms.
- **Standard transitions**: 200–300ms. Keep all UI animations strictly under 300ms.
- **Never use `ease-in` for UI entrances**: `ease-in` starts slow, making the interface feel sluggish and disconnected from the user's input. Always use `ease-out` or custom spring physics.
- **Transform & Opacity only**: Animate `transform` and `opacity` exclusively to prevent layout thrashing and maintain 60/120fps GPU acceleration.
- **Never animate from `scale(0)`**: Use `scale(0.95)` with `opacity: 0` for natural entrance physics.

## 3. Invisible Details & Component Polish

- **Developer Experience**: Zero complex setup or context wrappers where simple imperative/utility APIs suffice.
- **Seamless State Transitions**: Handle Edge cases invisibly (e.g., pause timers when tab is inactive, maintain hit target areas with pseudo-elements during drags).
- **Transitions over Keyframes**: Use interruptible CSS transitions or spring drivers rather than keyframe animations that restart abruptly on rapid triggers.
