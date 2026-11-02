---
name: design-taste-frontend
description: Prevents AI from generating generic, boring, or unpolished UI (UI slop). Implements high-end design systems, typography hierarchy, motion intensity, visual density, and thoughtful color schemes.
---

# Taste Skill (v2) - Frontend Design Directives

Use this skill when designing or implementing user interfaces, web applications, components, or visual layouts.

## Core Directives

1. **Anti-UI Slop**:
   - Never generate default unstyled inputs, standard blue/black hero sections with generic centered text, or placeholder card grids without purpose.
   - Avoid generic shadow utilities like `shadow-md` without custom elevation or ambient light color.

2. **Typography & Layout Hierarchy**:
   - Use high-contrast hierarchy (distinct font weights, tight letter-spacing on display headings).
   - Maintain tight visual density for power tools / dashboards, and generous whitespace for editorial/landing pages.

3. **Motion & Interaction**:
   - All interactive elements must have defined states: resting, hover, active/pressed, focus-visible.
   - Prefer subtle spring animations or custom cubic-bezier timing (`cubic-bezier(0.16, 1, 0.3, 1)`) over linear transitions.

4. **Color Systems**:
   - Use desaturated, rich background tones (e.g., slate, warm zinc, muted charcoal) instead of pure black `#000000` or raw primary blues unless explicitly requested.
   - Subtitle text should use clear opacity layering (e.g. `text-zinc-400` or `rgba(255,255,255,0.6)`).
