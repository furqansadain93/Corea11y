=== CoreA11y ===
Contributors: strugbits
Tags: accessibility, wcag, a11y, ada, audit
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit your site against WCAG 2.2 AA, see exactly what needs fixing, and track progress with before/after reports.

== Description ==

CoreA11y scans your pages with axe-core, the open-source accessibility testing engine, and shows every issue it finds grouped by WCAG criterion, page and severity, with a plain-language explanation of how to fix it.

* Full audit of your pages, run from your WordPress admin
* Clear report: what is wrong, where, and how to fix it
* Re-audit at any time and compare results before and after your fixes
* Manual checklist for the WCAG criteria that software can't judge, with step-by-step instructions

CoreA11y does not inject an "overlay" script into your site, and it never sends your content to an outside service.

**Important:** Automated tools find only part of all accessibility issues. CoreA11y reports automated scan results plus the manual checks you record. It does not certify that your site conforms to WCAG.

== Installation ==

1. Upload the plugin through Plugins > Add New, or install it from the WordPress.org directory.
2. Activate CoreA11y.
3. Go to CoreA11y > Dashboard and run your first audit.

== Frequently Asked Questions ==

= Does CoreA11y make my site WCAG compliant? =

No tool can do that automatically. CoreA11y shows you the issues automated testing can find and guides you through the manual checks that need a person.

= Does CoreA11y send my data anywhere? =

No. Scans run in your own browser and results are stored in your WordPress database.

= What happens to my data if I delete the plugin? =

By default, all CoreA11y audits and settings are removed. You can change this under CoreA11y > Settings.

== Third-party libraries ==

CoreA11y bundles axe-core 4.14.0 by Deque Systems (https://github.com/dequelabs/axe-core), licensed under the Mozilla Public License 2.0, which is GPL-compatible. The license is included in assets/vendor/axe-core/LICENSE.

The audit report links to Deque University rule pages (https://dequeuniversity.com/rules/axe/) for detailed fixing guidance. These links are only opened if you click them; no data is sent.

== Changelog ==

= 0.2.0 =
* Audit engine: scans pages in the admin with axe-core (WCAG 2.0/2.1/2.2 A and AA rules).
* Server-side checks for images without alt text in post content and the Media Library.
* Audit report grouped by problem with severity, WCAG criterion, fix type, affected elements and pages.
* Scan scope: one page per template, all published content, or a custom list of URLs.


= 0.1.0 =
* Initial development version: admin screens, settings, manual checklist, database tables.
