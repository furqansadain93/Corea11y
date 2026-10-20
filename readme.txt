=== CoreA11y ===
Contributors: furqansadain
Tags: accessibility, wcag, a11y, ada, audit
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit your site against WCAG 2.2 AA, see exactly what needs fixing, and track progress with before/after reports.

== Description ==

CoreA11y scans your pages with axe-core, the open-source accessibility testing engine, and shows every issue it finds grouped by WCAG criterion, page and severity, with a plain-language explanation of how to fix it.

* Full audit of your pages, run from your WordPress admin
* Quick fixes with one-click undo: reversible fixes for a skip link, keyboard focus outline, page language, mobile zoom, search and comment field labels, and missing page titles
* Visual report with a score gauge and charts by severity, WCAG principle and type of fix
* A "?" next to every problem explains in plain language why it matters, who it affects and how to fix it
* Before & after: compare any two audits to see what you fixed, what remains and what is new
* Download any report or comparison as a polished PDF to share with your team or clients
* Checks each page at desktop and phone width, and lists anything the automated checks can't decide on as "Needs a human check"
* Three layers of automated checks: axe-core, selected IBM Equal Access rules and CoreA11y's own checks for things like vague link text, placeholder-only fields and tables without headers
* Works with popular caching and optimization plugins: caches are cleared when you change a fix, and the fixes script is never delayed
* Manual checklist of 32 checks for what software can't judge, with step-by-step instructions
* Checks inside the block editor: every time you save, CoreA11y checks the post's content and explains any problems in the sidebar and before publishing
* "Show on page": see each problem outlined on your live page, with an explanation and next/previous navigation
* Dismiss issues that aren't real problems or that you accept for now, with a reason and note. Dismissals carry over to future audits and can be restored at any time.
* Accessibility statement generator: a live statement page that updates itself with your latest audit, following W3C guidance

CoreA11y does not inject an "overlay" script into your site, and it never sends your content to an outside service.

**Important:** Automated tools find only part of all accessibility issues. CoreA11y reports automated scan results plus the manual checks you record. It does not certify that your site conforms to WCAG.

== Installation ==

1. Upload the plugin through Plugins > Add New, or install it from the WordPress.org directory.
2. Activate CoreA11y.
3. Go to CoreA11y > Dashboard and run your first audit.

== Frequently Asked Questions ==

= Does CoreA11y make my site WCAG compliant? =

No tool can do that automatically. CoreA11y shows you the issues automated testing can find and guides you through the manual checks that need a person.

= Do quick fixes change my theme or content? =

No. Fixes are applied as your pages are displayed, using WordPress hooks and a small script for parts that themes hard-code. Turn a fix off and your site is exactly as before.

= How much can CoreA11y find automatically? =

No automated tool can find every accessibility problem. CoreA11y combines axe-core, selected IBM Equal Access rules and its own checks, lists anything it can't decide as "Needs a human check", and guides you through the rest with a 32-step manual checklist.

= Will CoreA11y slow down my site? =

No. The checking engines run only in your admin. Visitors only get the quick fixes you turn on: one small script (about 3 KB) and less than 1 KB of CSS.

= Does CoreA11y send my data anywhere? =

No. Scans run in your own browser and results are stored in your WordPress database.

= Does the accessibility statement claim my site is compliant? =

Only if you say so. You choose the conformance status yourself, and CoreA11y recommends "Partially conformant" until a full manual review has been done. It warns you if you choose "Fully conformant" while your latest audit still finds issues.

= What happens to my data if I delete the plugin? =

By default, all CoreA11y audits and settings are removed. Your accessibility statement page is your content and is kept. You can change this under CoreA11y > Settings.

== Screenshots ==

1. Dashboard: your latest score, progress and next steps at a glance.
2. Audit report with a score gauge and charts by severity, WCAG principle and type of fix.
3. Every problem has a "?" that explains why it matters, who it affects and how to fix it.
4. Before & after: compare any two audits to see what you fixed, what remains and what is new.
5. Quick fixes: safe, one-click fixes, each with undo.
6. Show on page: each problem outlined on your live site, with an explanation.
7. Checks inside the block editor every time you save a post.
8. Accessibility statement generator that stays up to date with your audits.
9. Download any report or comparison as a polished PDF.

== Third-party libraries ==

CoreA11y bundles axe-core 4.14.0 by Deque Systems (https://github.com/dequelabs/axe-core), licensed under the Mozilla Public License 2.0, which is GPL-compatible. The license is included in assets/vendor/axe-core/LICENSE.

CoreA11y bundles the IBM Equal Access Accessibility Checker engine 4.0.34 (accessibility-checker-engine, https://github.com/IBMa/equal-access), licensed under the Apache License 2.0, which is compatible with GPLv3. The license and notices are included in assets/vendor/ibm-equal-access/.

Both engines run only in the WordPress admin, while you run an audit or check a post. They are never loaded for site visitors.

The audit report links to Deque University rule pages (https://dequeuniversity.com/rules/axe/) for detailed fixing guidance. These links are only opened if you click them; no data is sent.

== Changelog ==

= 0.5.2 =
* Checks every page at phone width as well as desktop, to catch problems in mobile menus and layouts (can be turned off in Settings).
* New "Needs a human check" list in reports: items the automated checks can't decide on, shown separately and never counted in the score.
* New best-practice suggestions: empty headings, skipped heading levels, missing main heading, empty table headers, repeated alt text, positive tabindex and conflicting roles. Shown as minor and never counted in the score.
* 17 new CoreA11y checks for problems the main engine doesn't cover, including vague link text, file links without the file type, links that open new tabs without warning, placeholder-only fields, tables without headers, very small, justified or all-caps text, empty paragraphs and access keys.
* Extended checks: selected rules from the IBM Equal Access engine for form groups, fieldset legends, table headers and keyboard focus. Runs only in the admin during audits (can be turned off in Settings).
* Manual checklist now has 32 checks, covering zoom and reflow, text spacing, time limits, moving and flashing content, page titles, link text, language changes and more.
* Turning a quick fix on or off (or undoing it) now clears the page cache of popular caching plugins, so visitors see the change straight away.
* The quick fixes script is excluded from script delaying and combining in popular optimization plugins, so fixes work for keyboard and screen reader users right away.
* Pages that block framing with X-Frame-Options: DENY can now be scanned by administrators; clearer messages when a page can't be scanned.
* New "Support information" box in Settings to copy into a support request. Nothing is sent automatically.
* The score is now called the CoreA11y score, with a clearer explanation.
* Tested with MySQL/MariaDB.

= 0.5.1 =
* Passes the WordPress.org Plugin Check with no errors or warnings.
* Added a translation template (languages/corea11y.pot).
* Tested on WordPress 6.5 through 7.1.

= 0.5.0 =
* Dismiss issues with a reason and optional note; restore them at any time. Dismissed issues no longer count toward totals or the score, and stay dismissed in future audits.
* Accessibility statement generator with a live [corea11y_statement] shortcode, draft page creation and an optional footer link.
* Block editor panel: checks a post's content after every save and in the pre-publish panel, with plain-language explanations. Works with the cross-origin isolated editor in WordPress 7.1.
* Front-end highlighter: "Show on page" from any issue, plus an admin bar shortcut showing the number of issues on the current page.

= 0.4.0 =
* Quick fixes: six safe, one-click fixes with a full change history.
* Undo for every fix: undo a single fix, undo everything you just saved, undo any entry in the history, or redo an undo.
* Reports now point to the quick fix for each problem it can solve.
* "Verify with a new audit" re-scans right after you turn fixes on, so you can see what they resolved.

= 0.3.0 =
* New visual report: score gauge, charts by severity, WCAG principle and fix type, pages with the most issues.
* Plain-language "?" explanations for every problem and report term.
* Before & after comparison of any two audits, with resolved, remaining and new issues.
* PDF export for reports and comparisons.
* Friendlier dashboard with a getting-started guide and progress messages.

= 0.2.0 =
* Audit engine: scans pages in the admin with axe-core (WCAG 2.0/2.1/2.2 A and AA rules).
* Server-side checks for images without alt text in post content and the Media Library.
* Audit report grouped by problem with severity, WCAG criterion, fix type, affected elements and pages.
* Scan scope: one page per template, all published content, or a custom list of URLs.


= 0.1.0 =
* Initial development version: admin screens, settings, manual checklist, database tables.
