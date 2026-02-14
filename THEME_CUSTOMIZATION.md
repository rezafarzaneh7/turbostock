# TurboStock Theme Customization Guide

## Theme Architecture Overview

TurboStock uses a modular theme system with separated concerns for styling, layout, and functionality. The current theme follows a modern, clean design approach with responsive layouts and cryptocurrency-focused UI elements.

## Directory Structure

```
turbostock/
├── assets/
│   ├── css/
│   │   ├── style.css           # Main active stylesheet
│   │   ├── style-new.css       # Alternative/experimental styles
│   │   └── style-backup.css    # Backup of previous styles
│   ├── js/
│   │   └── main.js             # Core JavaScript functionality
│   └── images/                 # Static images and icons
├── includes/
│   ├── header.php              # HTML head and navigation
│   ├── footer.php              # Footer template
│   └── language files...
└── [page templates]            # PHP page files with embedded HTML
```

## Current Theme System

### CSS Framework
- **No External Framework**: Custom CSS without Bootstrap, Tailwind, etc.
- **Modern CSS Features**: Flexbox, CSS Grid, CSS Custom Properties
- **Responsive Design**: Mobile-first approach with media queries
- **Color System**: CSS custom properties for easy color management

### Design Philosophy
- **Clean & Modern**: Minimal, professional appearance
- **Crypto-Focused**: Designed for digital marketplace aesthetics
- **Performance**: Lightweight CSS with optimized selectors
- **Accessibility**: Semantic HTML with proper contrast ratios

## Theme Customization Areas

### 1. Color Scheme Customization

#### Current Color Palette
```css
:root {
    /* Primary Brand Colors */
    --primary-color: #3b82f6;      /* Blue primary */
    --primary-dark: #1e40af;       /* Darker blue */
    --primary-light: #93c5fd;      /* Light blue */

    /* Accent Colors */
    --accent-color: #10b981;       /* Green accent */
    --accent-warning: #f59e0b;     /* Orange warning */
    --accent-danger: #ef4444;      /* Red danger */

    /* Neutral Colors */
    --background: #ffffff;         /* Main background */
    --surface: #f8fafc;           /* Card/section backgrounds */
    --border: #e2e8f0;            /* Border color */
    --text-primary: #1e293b;      /* Main text */
    --text-secondary: #64748b;    /* Secondary text */
    --text-muted: #94a3b8;        /* Muted text */

    /* Dark Theme Support (Future) */
    --dark-background: #0f172a;
    --dark-surface: #1e293b;
    --dark-text: #f1f5f9;
}
```

#### Customizing Colors
To change the theme colors, modify the CSS custom properties in `assets/css/style.css`:

```css
/* Example: Purple Theme */
:root {
    --primary-color: #8b5cf6;
    --primary-dark: #7c3aed;
    --primary-light: #c4b5fd;
    --accent-color: #06b6d4;
}

/* Example: Dark Theme */
body.dark-theme {
    --background: var(--dark-background);
    --surface: var(--dark-surface);
    --text-primary: var(--dark-text);
    --text-secondary: #cbd5e1;
}
```

### 2. Typography System

#### Current Typography Stack
```css
/* Font Families */
--font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
--font-mono: 'SF Mono', Monaco, 'Cascadia Code', monospace;

/* Font Sizes */
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */
--text-4xl: 2.25rem;   /* 36px */

/* Font Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

#### Customizing Typography
```css
/* Example: Using Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

:root {
    --font-primary: 'Poppins', sans-serif;
}

/* Example: Larger Base Font Size */
:root {
    --text-base: 1.125rem; /* 18px instead of 16px */
}
```

### 3. Layout System

#### Container and Grid System
```css
/* Container Widths */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Responsive Grid */
.grid {
    display: grid;
    gap: 1.5rem;
}

.grid-cols-1 { grid-template-columns: 1fr; }
.grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
.grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
.grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

/* Flexbox Utilities */
.flex { display: flex; }
.flex-col { flex-direction: column; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-4 { gap: 1rem; }
```

#### Customizing Layout
```css
/* Example: Wider Container */
.container {
    max-width: 1400px; /* Instead of 1200px */
}

/* Example: Different Grid Gaps */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem; /* Larger gap */
}
```

### 4. Component Styling

#### Button System
```css
/* Base Button */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: var(--font-medium);
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

/* Button Variants */
.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-outline {
    background: transparent;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.btn-ghost {
    background: transparent;
    color: var(--text-primary);
}

/* Button Sizes */
.btn-sm { padding: 0.5rem 1rem; font-size: var(--text-sm); }
.btn-lg { padding: 1rem 2rem; font-size: var(--text-lg); }
```

#### Card System
```css
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Card Variants */
.card-elevated {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-product {
    position: relative;
    overflow: hidden;
}

.card-product::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}
```

### 5. Navigation Customization

#### Header Navigation
```css
/* Main Header */
.main-header {
    background: var(--background);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
}

/* Navigation Links */
.nav-link {
    color: var(--text-secondary);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.nav-link:hover,
.nav-link.active {
    color: var(--primary-color);
    background: rgba(59, 130, 246, 0.1);
}

/* Mobile Navigation */
@media (max-width: 768px) {
    .nav-menu {
        position: fixed;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--background);
        border-top: 1px solid var(--border);
        transform: translateY(-100%);
        opacity: 0;
        transition: all 0.3s ease;
    }

    .nav-menu.active {
        transform: translateY(0);
        opacity: 1;
    }
}
```

#### Sidebar Navigation (Admin/Seller)
```css
.sidebar {
    background: var(--surface);
    border-right: 1px solid var(--border);
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 999;
    overflow-y: auto;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.2s ease;
}

.sidebar-link:hover,
.sidebar-link.active {
    background: rgba(59, 130, 246, 0.1);
    color: var(--primary-color);
    border-right: 3px solid var(--primary-color);
}
```

### 6. Responsive Design

#### Breakpoint System
```css
/* Mobile First Approach */
:root {
    --breakpoint-sm: 640px;
    --breakpoint-md: 768px;
    --breakpoint-lg: 1024px;
    --breakpoint-xl: 1280px;
}

/* Usage Examples */
.product-grid {
    grid-template-columns: 1fr; /* Mobile default */
}

@media (min-width: 640px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
```

#### Responsive Utilities
```css
/* Visibility Classes */
.hidden-mobile { display: none; }
.hidden-desktop { display: block; }

@media (min-width: 768px) {
    .hidden-mobile { display: block; }
    .hidden-desktop { display: none; }
}

/* Responsive Text Sizes */
.text-responsive {
    font-size: var(--text-sm);
}

@media (min-width: 768px) {
    .text-responsive {
        font-size: var(--text-base);
    }
}

@media (min-width: 1024px) {
    .text-responsive {
        font-size: var(--text-lg);
    }
}
```

## Theme Development Workflow

### 1. Setting Up Development Environment

#### CSS Development
```bash
# Create a new theme variant
cp assets/css/style.css assets/css/style-custom.css

# Edit the custom theme
nano assets/css/style-custom.css

# Test changes by temporarily linking in header.php
```

#### Live Reloading (Optional)
```javascript
// Simple live reload script (add to header.php during development)
<script>
if (window.location.hostname === 'localhost') {
    setInterval(() => {
        fetch('/assets/css/style.css?' + Date.now())
            .then(() => {
                // Reload stylesheets
                document.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
                    link.href = link.href.split('?')[0] + '?' + Date.now();
                });
            });
    }, 2000);
}
</script>
```

### 2. Theme Switching System

#### Dynamic Theme Loading
```php
// In config/config.php or includes/functions.php
function getThemeCSS() {
    $theme = getCurrentUser()['theme'] ?? getSetting('default_theme', 'default');

    $themeFiles = [
        'default' => 'style.css',
        'dark' => 'style-dark.css',
        'modern' => 'style-modern.css',
        'minimal' => 'style-minimal.css'
    ];

    return $themeFiles[$theme] ?? $themeFiles['default'];
}

// In includes/header.php
$themeCSS = getThemeCSS();
echo '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/' . $themeCSS . '">';
```

#### User Theme Preferences
```php
// Add to user preferences table
ALTER TABLE users ADD COLUMN theme VARCHAR(50) DEFAULT 'default';

// Theme switcher component
function renderThemeSwitcher() {
    $themes = [
        'default' => 'Default',
        'dark' => 'Dark Mode',
        'modern' => 'Modern',
        'minimal' => 'Minimal'
    ];

    $currentTheme = getCurrentUser()['theme'] ?? 'default';

    echo '<select name="theme" onchange="updateTheme(this.value)">';
    foreach ($themes as $key => $name) {
        $selected = ($key === $currentTheme) ? 'selected' : '';
        echo "<option value=\"{$key}\" {$selected}>{$name}</option>";
    }
    echo '</select>';
}
```

### 3. Component-Based Styling

#### Reusable Component Classes
```css
/* Product Card Component */
.product-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
}

.product-card__image {
    aspect-ratio: 16/9;
    object-fit: cover;
    width: 100%;
}

.product-card__content {
    padding: 1.5rem;
}

.product-card__title {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.product-card__price {
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--accent-color);
}

.product-card__meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

/* Status Badge Component */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    text-transform: uppercase;
}

.badge--success {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.badge--warning {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.badge--danger {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

/* Form Component */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border);
    border-radius: 0.5rem;
    font-size: var(--text-base);
    transition: border-color 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
```

### 4. Animation and Transitions

#### CSS Animation Library
```css
/* Fade In Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease;
}

/* Slide In Animation */
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-slide-in-right {
    animation: slideInRight 0.3s ease;
}

/* Hover Effects */
.hover-lift {
    transition: transform 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-2px);
}

/* Loading Animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Scale Animation */
.scale-on-hover {
    transition: transform 0.2s ease;
}

.scale-on-hover:hover {
    transform: scale(1.05);
}
```

### 5. Dark Mode Implementation

#### CSS Custom Properties Approach
```css
/* Light Theme (Default) */
:root {
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}

/* Dark Theme */
[data-theme="dark"] {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
}

/* Apply theme variables */
body {
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
}

.card {
    background: var(--bg-secondary);
    border-color: var(--border-color);
}
```

#### JavaScript Theme Toggle
```javascript
// Theme toggle functionality
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.apply();
    }

    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        this.apply();
        this.save();
    }

    apply() {
        document.documentElement.setAttribute('data-theme', this.theme);

        // Update any theme-specific icons or text
        const themeIcons = document.querySelectorAll('.theme-icon');
        themeIcons.forEach(icon => {
            icon.textContent = this.theme === 'dark' ? '🌙' : '☀️';
        });
    }

    save() {
        localStorage.setItem('theme', this.theme);

        // Save to user preferences (requires API call)
        if (window.currentUserId) {
            fetch('/api/update-theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: this.theme })
            });
        }
    }
}

// Initialize theme manager
const themeManager = new ThemeManager();

// Theme toggle button
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            themeManager.toggle();
        });
    }
});
```

## Advanced Customization

### 1. CSS-in-JS Integration (Future Enhancement)
```javascript
// Dynamic styling with JavaScript
class StyleManager {
    constructor() {
        this.styles = new Map();
    }

    addStyle(selector, styles) {
        const stylesheet = document.styleSheets[0];
        const ruleText = `${selector} { ${Object.entries(styles).map(([prop, value]) => `${prop}: ${value}`).join('; ')} }`;
        stylesheet.insertRule(ruleText, stylesheet.cssRules.length);
    }

    updateCustomProperty(property, value) {
        document.documentElement.style.setProperty(property, value);
    }

    generateTheme(config) {
        const { primaryColor, accentColor, borderRadius, fontFamily } = config;

        this.updateCustomProperty('--primary-color', primaryColor);
        this.updateCustomProperty('--accent-color', accentColor);
        this.updateCustomProperty('--border-radius', borderRadius);
        this.updateCustomProperty('--font-primary', fontFamily);
    }
}
```

### 2. Theme Builder Interface
```php
// Admin theme builder
function renderThemeBuilder() {
    $currentTheme = getSetting('active_theme', 'default');
    ?>
    <div class="theme-builder">
        <h3>Theme Customization</h3>

        <div class="theme-controls">
            <!-- Color Picker -->
            <div class="control-group">
                <label>Primary Color</label>
                <input type="color" id="primary-color" value="#3b82f6">
            </div>

            <!-- Font Selection -->
            <div class="control-group">
                <label>Font Family</label>
                <select id="font-family">
                    <option value="Inter">Inter</option>
                    <option value="Poppins">Poppins</option>
                    <option value="Roboto">Roboto</option>
                </select>
            </div>

            <!-- Border Radius -->
            <div class="control-group">
                <label>Border Radius</label>
                <input type="range" id="border-radius" min="0" max="20" value="6">
            </div>

            <!-- Live Preview -->
            <div class="preview-area">
                <div class="preview-card">
                    <h4>Preview Card</h4>
                    <p>This is how your theme will look.</p>
                    <button class="btn btn-primary">Sample Button</button>
                </div>
            </div>

            <button onclick="saveCustomTheme()">Save Theme</button>
        </div>
    </div>

    <script>
    function saveCustomTheme() {
        const theme = {
            primaryColor: document.getElementById('primary-color').value,
            fontFamily: document.getElementById('font-family').value,
            borderRadius: document.getElementById('border-radius').value + 'px'
        };

        fetch('/admin/save-theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(theme)
        });
    }
    </script>
    <?php
}
```

## Performance Optimization

### 1. CSS Optimization
```css
/* Critical CSS (Above-the-fold content) */
.critical-above-fold {
    /* Only styles needed for immediate page load */
}

/* Non-critical CSS (Lazy loaded) */
@media (min-width: 1px) {
    /* Load after critical CSS */
    .non-critical-styles {
        /* Remaining styles */
    }
}
```

### 2. Asset Optimization
```php
// CSS/JS minification and caching
function getOptimizedAssets($files, $type = 'css') {
    $cacheKey = md5(implode(':', $files));
    $cacheFile = "cache/{$type}_{$cacheKey}.{$type}";

    if (!file_exists($cacheFile) || !PRODUCTION_MODE) {
        $content = '';
        foreach ($files as $file) {
            $content .= file_get_contents($file);
        }

        // Minify content
        if ($type === 'css') {
            $content = minifyCSS($content);
        } elseif ($type === 'js') {
            $content = minifyJS($content);
        }

        file_put_contents($cacheFile, $content);
    }

    return BASE_URL . '/' . $cacheFile;
}

// Usage in header.php
$cssFiles = [
    'assets/css/style.css',
    'assets/css/components.css'
];
$optimizedCSS = getOptimizedAssets($cssFiles, 'css');
echo "<link rel=\"stylesheet\" href=\"{$optimizedCSS}\">";
```

## Best Practices

### 1. CSS Organization
- **Component-based**: Organize styles by component, not page
- **Utility classes**: Create reusable utility classes for common patterns
- **Custom properties**: Use CSS custom properties for theme consistency
- **Progressive enhancement**: Build mobile-first, enhance for larger screens

### 2. Performance Guidelines
- **Critical CSS**: Inline critical above-the-fold styles
- **Lazy loading**: Load non-critical styles asynchronously
- **Compression**: Minify and gzip CSS/JS files
- **Caching**: Implement proper cache headers for static assets

### 3. Accessibility Standards
- **Color contrast**: Maintain WCAG AA color contrast ratios
- **Focus states**: Ensure all interactive elements have visible focus states
- **Semantic HTML**: Use proper HTML semantics for screen readers
- **Responsive text**: Ensure text scales properly on all devices

### 4. Browser Support
- **Modern browsers**: Support latest 2 versions of major browsers
- **Graceful degradation**: Provide fallbacks for newer CSS features
- **Testing**: Test on multiple browsers and devices

## Migration and Deployment

### 1. Theme Migration Process
```bash
# 1. Backup current theme
cp -r assets/css/ assets/css-backup/

# 2. Deploy new theme files
cp new-theme/style.css assets/css/
cp new-theme/components.css assets/css/

# 3. Update references in PHP files
grep -r "style.css" . --include="*.php"

# 4. Test on staging environment
# 5. Deploy to production with rollback plan
```

### 2. Version Control
```bash
# Track theme changes
git add assets/css/
git commit -m "feat: update theme with new color scheme"

# Tag theme versions
git tag -a "theme-v2.0" -m "Major theme redesign"
```

This comprehensive theme customization guide provides the foundation for maintaining and evolving the TurboStock visual design while ensuring maintainability, performance, and user experience.