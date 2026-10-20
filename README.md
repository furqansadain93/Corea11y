# CoreA11y

WordPress accessibility plugin: audit a site against WCAG 2.2 AA with axe-core, guide the owner through fixes, and compare before/after results.

This repo holds **CoreA11y Lite**, the free plugin for the WordPress.org directory. CoreA11y Pro (automatic fixes, approvals, undo, deeper scans) is a separate add-on that hooks into Lite through the `corea11y_loaded` action.

## Requirements

- WordPress 6.5+
- PHP 7.4+

## Structure

```
corea11y.php            Plugin header and bootstrap
uninstall.php           Data removal on delete
readme.txt              WordPress.org listing
includes/               PHP classes (install, settings, admin, REST, checklist)
admin/views/            Admin screen templates
assets/css/             Admin styles
assets/js/scanner.js    Browser-side scanner (runs axe-core in a hidden frame)
assets/vendor/axe-core/ Bundled axe-core (MPL-2.0)
languages/              Translation template (corea11y.pot)
.wordpress-org/         Screenshots for the WordPress.org listing (not shipped in the plugin)
```

## Development status

- [x] Milestone 1: plugin skeleton, tables, admin screens, settings, manual checklist
- [x] Milestone 2: audit engine (axe-core scanning, server-side checks, report)
- [x] Milestone 3: visual reports, "?" explanations, before/after comparison, PDF export
- [x] v0.4.0: six quick fixes (skip link, focus outline, page language, zoom, form labels, page title)
- [x] v0.5.0: dismiss issues, accessibility statement, block editor checks, front-end highlighter
- [x] v0.5.1: passes Plugin Check (0 errors, 0 warnings), translation template, tested on WordPress 6.5 to 7.1, listing screenshots
- [x] v0.5.2: phone-width scans, "Needs a human check", cache clearing and optimizer compatibility, clearer scan errors, support info, tested on MySQL/MariaDB
- [x] Submitted to WordPress.org (in review)

## License

GPL-2.0-or-later
