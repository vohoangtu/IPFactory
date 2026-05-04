# Plan: Frontend Visual Consistency Overhaul

## Goal
Eliminate hardcoded Tailwind values, establish a single source of truth for colors, radius, and shadows, and enforce consistency across all pages and components.

## Approach
Use **Tailwind CSS v4 `@theme` directive** to register design tokens as first-class Tailwind utilities. This is the modern, canonical way in Tailwind v4 (no `tailwind.config.js`).

---

## Phase 1: Expand Token System via `@theme`

Add semantic tokens to `globals.css` so components can write `bg-surface text-muted border-subtle rounded-card` instead of `bg-slate-950/40 text-slate-500 border-slate-800 rounded-[32px]`.

### New tokens to add:

```css
@theme {
  /* Backgrounds */
  --color-bg-base:     var(--bg-base);
  --color-bg-surface:  var(--bg-surface);
  --color-bg-elevated: var(--bg-elevated);
  --color-bg-overlay:  var(--bg-overlay);

  /* Brand / Semantic */
  --color-brand-primary: var(--color-primary);
  --color-brand-accent:  var(--color-accent);
  --color-brand-emerald: var(--color-emerald);
  --color-brand-amber:   var(--color-amber);
  --color-brand-danger:  var(--color-danger);
  --color-brand-info:    var(--color-info);

  /* Text (semantic) */
  --color-text-primary:   hsl(220, 20%, 96%);
  --color-text-secondary: hsl(220, 13%, 72%);
  --color-text-muted:     hsl(220, 10%, 52%);
  --color-text-disabled:  hsl(220, 9%, 38%);

  /* Borders */
  --color-border-subtle:  var(--border-subtle);
  --color-border-muted:   var(--border-muted);
  --color-border-primary: var(--border-primary);

  /* Radius scale */
  --radius-sm:   0.5rem;    /* 8px  */
  --radius-md:   0.75rem;   /* 12px */
  --radius-lg:   1rem;      /* 16px */
  --radius-xl:   1.25rem;   /* 20px */
  --radius-2xl:  1.5rem;    /* 24px */
  --radius-3xl:  2rem;      /* 32px */

  /* Shadows / Glows */
  --shadow-glow-cyan:    0 0 24px rgba(110, 231, 247, 0.08);
  --shadow-glow-violet:  0 0 24px rgba(167, 139, 250, 0.08);
  --shadow-glow-amber:   0 0 24px rgba(245, 158, 11, 0.08);
  --shadow-glow-emerald: 0 0 24px rgba(52, 211, 153, 0.08);
  --shadow-glow-danger:  0 0 24px rgba(248, 113, 113, 0.08);
}
```

This enables classes like:
- `bg-surface hover:bg-elevated`
- `text-muted`
- `border-subtle`
- `rounded-xl`
- `shadow-glow-cyan`

### Keep legacy `:root` tokens intact for any runtime JS reads.

---

## Phase 2: Create Standardized Primitives

### 2A. `Button` component
A single source of truth for all buttons, ending the copy-paste Tailwind drift.

Variants:
- `primary`   → cyan border + cyan text + cyan bg tint
- `secondary` → subtle border + secondary text
- `ghost`     → transparent, hover bg
- `danger`    → danger border + danger text
- `outline`   → any brand color outline

Sizes: `sm`, `md`, `lg`

### 2B. `Surface` component
Reusable card/panel wrapper to replace the repeated pattern:
```
bg-slate-950/40 border border-slate-800 rounded-xxx
```

Props:
- `variant`: `'default' | 'elevated' | 'glass'`
- `radius`: `'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl'`
- `padding`: `'none' | 'sm' | 'md' | 'lg'`
- `border`: `boolean`

---

## Phase 3: Migrate Shared UI Components

Audit and refactor all files in `components/ui/shared/`:

| Component | Action |
|-----------|--------|
| `BadgeLabel.tsx` | Replace `text-glow-*` hardcodes with token classes; use `cn()` |
| `EmptyState.tsx` | Use `Surface`, `Button`, `text-muted`, `bg-surface` |
| `FilterToolbar.tsx` | Use `bg-surface`, `border-subtle`, `text-muted`; standard input styles |
| `GaugeCard.tsx` | Replace `bg-slate-900`, `text-slate-400` with tokens; glow tokens |
| `LoadingSpinner.tsx` | Use `text-brand-primary` |
| `ModalShell.tsx` | Close button → `Surface` or `Button ghost`; `text-muted` |
| `ProgressBar.tsx` | Color thresholds → brand tokens |
| `SectionPanel.tsx` | Use `Surface variant="default" radius="3xl"` |
| `StatCard.tsx` | Replace `text-slate-500`, `border-slate-700/50` with tokens; glow tokens |

Also fix: `BadgeLabel` duplicate variants (`danger` === `rose`, `success` === `emerald`) — dedupe.

---

## Phase 4: Audit & Migrate Key Pages

Focus on the most visible / problematic pages:

1. **Dashboard Shell** (`components/shell/DashboardShell.tsx`)
   - Sidebar: `bg-slate-950` → `bg-surface`
   - Nav items: `text-slate-400` → `text-muted`
   - Header: `border-slate-800` → `border-subtle`

2. **Login Page** (`app/login/page.tsx`)
   - Card: hardcoded bg → `Surface`
   - Inputs: standardize border/focus styles
   - Button: use `Button primary`

3. **Dashboard Home** (`app/dashboard/page.tsx`)
   - Stat cards: already use `StatCard` → verify tokens inside
   - Tabs: standardize active/inactive styles
   - Remove duplicate utility functions (already in `lib/utils.ts`)

4. **Loom Workshop** (`app/dashboard/loom-workshop/`)
   - Tab bars: standardize
   - Panels: use `Surface`
   - Button drift: consolidate to `Button`

5. **Achievement / Timeline pages** (newly built)
   - `AchievementCard`, `AchievementGrid`, `WorldTimeline`, `TimelineNode`
   - Replace all hardcoded colors with tokens
   - Standardize radius

6. **Narrative Studio** (`app/narrative-studio/`)
   - 3-column layout panels → `Surface`
   - Form buttons → `Button`

---

## Phase 5: Global Cleanup

1. **Remove arbitrary values**:
   - Replace `rounded-[20px]`, `rounded-[28px]`, `rounded-[32px]` → `rounded-xl`, `rounded-2xl`, `rounded-3xl`
   - Replace inline `shadow-[0_0_30px_...]` → `shadow-glow-*` tokens

2. **Consolidate gray families**:
   - Eliminate mixing `gray-*`, `slate-*`, `zinc-*`
   - Standardize on semantic `text-*` tokens

3. **Shadcn decision**:
   - `shadcn-ui` package is installed but unused
   - Either remove it from `package.json` OR install actual shadcn primitives (`button`, `card`, `dialog`) and theme them to match our tokens
   - **Decision**: Keep `shadcn-ui` CLI but rename our custom path to avoid collision. Actually simpler: keep our custom `components/ui/` and drop `shadcn-ui` dep. Less overhead, full control.

---

## Deliverables

| # | Deliverable | Files |
|---|-------------|-------|
| 1 | Updated `globals.css` with `@theme` tokens | 1 |
| 2 | New `Button.tsx` primitive | 1 |
| 2 | New `Surface.tsx` primitive | 1 |
| 3 | Refactored `components/ui/shared/*` | 9 |
| 4 | Refactored shell, login, dashboard home | 3-4 |
| 5 | Refactored loom-workshop, narrative-studio | 5-8 |
| 6 | Refactored achievement / timeline | 4 |

## Estimated Effort
- Phase 1 (tokens): 30 min
- Phase 2 (primitives): 45 min
- Phase 3 (shared components): 60 min
- Phase 4 (pages): 90 min
- Phase 5 (cleanup): 30 min
- **Total: ~4-5 hours**

## Testing
- Run `npx tsc --noEmit` after each phase
- Run Docker build at the end to confirm no regressions
- Visual smoke-test on key pages
