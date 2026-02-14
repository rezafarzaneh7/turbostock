---
description: CSS file management for the redesign — themes, scoping, components
---

You are the CSS specialist for the TurboStock redesign. You work exclusively with `assets/css/style-new-design.css`.

**Task:** $ARGUMENTS

## CSS Architecture

**File:** `assets/css/style-new-design.css` (the only active stylesheet, loaded by `includes/header.php`)

**Page Scoping Pattern:** Every page's CSS is scoped with a `body.page-{name}` prefix and introduced by a comment header:
```css
/* page: {name} */
body.page-{name} .some-element {
    /* styles */
}
```

## Theme System

Two themes controlled by class on `<body>`, toggled via JS with `localStorage`:

```css
/* Dark theme (default) — defined on :root or body */
--bg-primary: #0a0a0a;
--bg-secondary: #111111;
--bg-tertiary: #161616;
--bg-card: #1a1a1a;
--bg-elevated: #222222;
--accent-teal: #00A38F;
--accent-teal-dark: #008573;
--accent-orange: #ff980e;
--text-primary: #FFFFFF;
--text-secondary: #b3b3b3;
--text-muted: #737373;
--border-primary: #2a2a2a;
--border-secondary: #333333;
--shadow-black: 4px 4px 0 #000;
--gradient-teal: linear-gradient(135deg, #00A38F 0%, #00d4aa 100%);

/* Light theme overrides (body.theme-light) */
--bg-primary: #ffffff;
--bg-secondary: #e9f3ef;
--bg-card: #ffffff;
--text-primary: #191C1B;
--text-secondary: #5C5F5E;
--border-primary: #E0E3E1;
```

**Rule:** Always use CSS variables for colors. Never hardcode hex values in page-scoped styles.

## Adding a New Page Section

Template for adding CSS for a new page:

```css
/* =============================================
   page: {name}
   ============================================= */
body.page-{name} .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

/* Mobile */
@media (max-width: 768px) {
    body.page-{name} .container {
        padding: 1rem;
    }
}
```

## Common Component Patterns

### Card
```css
body.page-{name} .card {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    border-radius: 16px;
    padding: 2rem;
}
```

### Primary Button
```css
body.page-{name} .btn-primary {
    background: var(--gradient-teal);
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}
body.page-{name} .btn-primary:hover {
    opacity: 0.9;
}
```

### Data Table
```css
body.page-{name} .data-table {
    width: 100%;
    border-collapse: collapse;
}
body.page-{name} .data-table th {
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    font-size: 0.85rem;
    text-transform: uppercase;
}
body.page-{name} .data-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-primary);
    color: var(--text-primary);
}
```

### Stats Grid
```css
body.page-{name} .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}
body.page-{name} .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    border-radius: 12px;
    padding: 1.5rem;
}
body.page-{name} .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--accent-teal);
}
body.page-{name} .stat-label {
    color: var(--text-muted);
    font-size: 0.875rem;
}
```

### Sidebar + Main Layout
```css
body.page-{name} .page-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 2rem;
}

@media (max-width: 992px) {
    body.page-{name} .page-layout {
        grid-template-columns: 1fr;
    }
}
```

## Responsive Breakpoints

- `768px` — Mobile/tablet switch (collapse grids, stack layouts)
- `992px` — Tablet/desktop switch (sidebar collapses)
- `1200px` — Large desktop adjustments

## Debugging Tips

- Use browser DevTools to toggle `theme-light` / `theme-dark` class on `<body>`
- Check that `body.page-{name}` class is actually applied (inspect `<body>` element)
- Search for hardcoded hex values in the CSS section — replace with variables
- Test at 320px, 768px, 1024px, 1440px viewport widths

Proceed with the CSS task specified above.
