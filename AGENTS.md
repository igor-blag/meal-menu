# AI Instructions
This is a WordPress plugin for meal menu publication.
Complete description of this plugin in folder /docs

Symlink: `/Users/igorblagovesenskij/Studio/edu-site/wp-content/plugins/meal-menu`
  → `/Users/igorblagovesenskij/projects/plugins/meal-menu`

## Database access (SQLite)
Site path: `/Users/igorblagovesenskij/Studio/edu-site`
DB file: `/Users/igorblagovesenskij/Studio/edu-site/wp-content/database/.ht.sqlite`

Direct SQLite (site not needed):
  sqlite3 -header -column "/Users/igorblagovesenskij/Studio/edu-site/wp-content/database/.ht.sqlite" "SQL_QUERY"

Via WP-CLI (site must be running):
  1. Write PHP eval-file script using $wpdb->get_results()
  2. Run: wp eval-file script.php

## Site
- URL: http://localhost:8881
- Admin: admin / password
- WP: 7.0, PHP: 8.4, SQLite via WordPress SQLite plugin

## Plugin structure (meal-menu)
- `includes/class-core.php` — Core: init, shortcodes, admin pages, AJAX, render helpers
- `includes/class-db.php` — DB: CRUD for calendar, templates, departments, vacations, OC
- `includes/class-activator.php` — Activation: table creation, seed data, cron
- `templates/public/` — public templates (day.php only; calendar renders via Core methods)
- `templates/admin/` — admin page templates
- `assets/css/` — public.css (structural), themes.css (palettes + layouts), admin.css
- `assets/js/` — public.js (AJAX nav, modal), admin.js

## Current shortcodes
- `[meal_calendar type="sm"]` — календарь питания (рендерится из Core, AJAX-навигация, модалка)
- `[meal_day date="2025-06-01" type="sm"]` — меню на день (templates/public/day.php)

Removed: `[meal_menu]` (заменён модалкой), `[meal_oc]` (встроен в календарь как спойлер).

## States for Gutenberg block (block-development.md)
The plugin needs a **Gutenberg block** that renders the meal calendar (`[meal_calendar]`) inside the block editor and on the frontend.

### Key render methods (in `includes/class-core.php`)
- `Core::shortcode_calendar()` — full calendar wrapper (title, calendar body, modal, footer, OC spoiler)
- `Core::render_calendar_body($type, $year, $month)` — returns HTML for tabs + nav + grid + legend (used by shortcode and AJAX)
- `Core::render_oc_content()` — returns HTML for the public control spoiler

### AJAX endpoints for the block
- `admin-ajax.php?action=meal_get_calendar&meal_type=X&meal_y=Y&meal_m=Z` — returns JSON `{ok, html}`
- `admin-ajax.php?action=meal_get_day_menu&date=YYYY-MM-DD&type=X&nonce=N` — returns JSON `{ok, html}`

### Data flow
1. Templates (`wp_meal_templates`) define cycle days with items
2. Calendar (`wp_meal_calendar`) maps dates to templates per school_type
3. Departments (`wp_meal_departments`) define school types, merged calendars, workdays
4. Vacations (`wp_meal_vacations`) define holidays and vacation periods

### CSS architecture
- Structural styles: `assets/css/public.css` (BEM-like classes)
- Theme variables: `assets/css/themes.css` (7 palettes × 3 layouts = 21 combinations)
- Variables `--meal-*` control all colors; `--meal-font`, `--meal-radius`, `--meal-grid-gap` control layout
- Modal: uses `--meal-modal-bg` for background

### Responsive breakpoints
- Mobile: `@media (max-width: 600px)` — horizontal scroll for grid, hidden labels, smaller cells
- Modal table: scrollable on mobile (`overflow-x: auto`)

