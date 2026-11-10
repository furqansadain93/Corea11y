=== MSACAlab Accessibility Checker ===
Contributors: furqansadain
Tags: accessibility, wcag, a11y, ada, audit
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit your site against WCAG 2.2 AA, see exactly what needs fixing, and track progress with before/after reports.

== Description ==

MSACAlab Accessibility Checker scans your pages with axe-core, the open-source accessibility testing engine, and shows every issue it finds grouped by WCAG criterion, page and severity, with a plain-language explanation of how to fix it.

* Full audit of your pages, run from your WordPress admin
* 12 quick fixes with one-click undo: a skip link, keyboard focus outline, page language, mobile zoom, search and comment field labels, missing page titles, titles for embedded videos and maps, hidden empty headings and paragraphs, natural keyboard order, no clashing shortcut keys, and respect for "reduce motion" settings
* Alt text screen: every image missing alt text in one list. Type a description (or mark it decorative) and save: MSACAlab Accessibility Checker adds it to the Media Library and to the pages where the image is already used. Undo is one click, and the alt text stays even if you remove MSACAlab Accessibility Checker.
* Fixed and verified: after you turn a fix on, MSACAlab Accessibility Checker re-checks the affected pages and shows whether the problems are gone
* Visual report with a score gauge and charts by severity, WCAG principle and type of fix
* A "?" next to every problem explains in plain language why it matters, who it affects and how to fix it
* Before & after: compare any two audits to see what you fixed, what remains and what is new
* Download any report or comparison as a polished PDF to share with your team or clients
* Checks each page at desktop and phone width, and lists anything the automated checks can't decide on as "Needs a human check"
* Three layers of automated checks: axe-core, selected IBM Equal Access rules and MSACAlab Accessibility Checker's own checks for things like vague link text, placeholder-only fields and tables without headers
* Works with popular caching and optimization plugins: caches are cleared when you change a fix, and the fixes script is never delayed
* Manual checklist of 32 checks for what software can't judge, with step-by-step instructions
* Checks inside the block editor: every time you save, MSACAlab Accessibility Checker checks the post's content and explains any problems in the sidebar and before publishing
* "Show on page": see each problem outlined on your live page, with an explanation and next/previous navigation
* Dismiss issues that aren't real problems or that you accept for now, with a reason and note. Dismissals carry over to future audits and can be restored at any time.
* Accessibility statement generator: a live statement page that updates itself with your latest audit, following W3C guidance

MSACAlab Accessibility Checker does not inject an "overlay" script into your site, and it never sends your content to an outside service.

= For sites in Europe =

The European Accessibility Act (EAA) applies to many online shops and services in the EU from June 2025. Its harmonised standard, EN 301 549, uses the WCAG 2.1 Level AA success criteria for web content, and MSACAlab Accessibility Checker tests against WCAG 2.2 AA, which builds on WCAG 2.1. The plugin helps you find and fix problems and write an accessibility statement; it does not certify that your site meets the EAA or any other law.

= Languages =

MSACAlab Accessibility Checker is available in English, German, French and Spanish (formal address), including the axe-core rule summaries and the accessibility statement. The German, French and Spanish translations are new: if any wording could be clearer, please tell us at support@corea11y.com. You are also welcome to help translate the plugin on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/msacalab-accessibility-checker/).

= MSACAlab Accessibility Checker Pro =

Everything above is free, with no limits on pages or audits. [MSACAlab Accessibility Checker Pro](https://corea11y.com/pricing/) is an optional upgrade for people who want to fix more in one click:

* 29 more quick fixes, all undoable: names for icon-only links and buttons, keyboard-friendly dropdown menus, new-tab warnings, form field names and error messages, table headers, stopping autoplay and more
* Colour contrast fixes you approve: suggests the nearest readable colour for each problem, applied only after you approve it
* Weekly check: an email when new or changed pages need an audit
* CSV export and multisite network overview (Business and Agency)
* White-label PDF reports with your own logo and colours (Agency)

Plans start at $19 a month or $179 a year for one site, with a 30-day money-back guarantee. [See plans and pricing](https://corea11y.com/pricing/).

**Important:** Automated tools find only part of all accessibility issues. MSACAlab Accessibility Checker reports automated scan results plus the manual checks you record. It does not certify that your site conforms to WCAG.

== Installation ==

1. Upload the plugin through Plugins > Add New, or install it from the WordPress.org directory.
2. Activate MSACAlab Accessibility Checker.
3. Go to Accessibility > Dashboard and run your first audit.

== Frequently Asked Questions ==

= Which languages are available? =

English, German, French and Spanish. MSACAlab Accessibility Checker follows the language set for your user profile (or Settings > General > Site Language), and the public accessibility statement follows the site language. Other languages show in English.

= What is the difference between the free version and Pro? =

The free version includes every check, the full report, 12 quick fixes, the Alt text screen, PDF reports and the statement generator. Pro adds 29 more quick fixes, colour contrast fixes you approve, a weekly check and agency tools. Installing Pro replaces the free version and keeps all your audits and settings. [Compare plans](https://corea11y.com/pricing/).

= Does MSACAlab Accessibility Checker make my site WCAG compliant? =

No tool can do that automatically. MSACAlab Accessibility Checker shows you the issues automated testing can find and guides you through the manual checks that need a person.

= Do quick fixes change my theme or content? =

No. Fixes are applied as your pages are displayed, using WordPress hooks and a small script for parts that themes hard-code. Turn a fix off and your site is exactly as before.

= How much can MSACAlab Accessibility Checker find automatically? =

No automated tool can find every accessibility problem. MSACAlab Accessibility Checker combines axe-core, selected IBM Equal Access rules and its own checks, lists anything it can't decide as "Needs a human check", and guides you through the rest with a 32-step manual checklist.

= Will MSACAlab Accessibility Checker slow down my site? =

No. The checking engines run only in your admin. Visitors only get the quick fixes you turn on: one small script (under 2 KB compressed) and less than 1 KB of CSS.

= Does MSACAlab Accessibility Checker send my data anywhere? =

No. Scans run in your own browser and results are stored in your WordPress database.

= Does the accessibility statement claim my site is compliant? =

Only if you say so. You choose the conformance status yourself, and MSACAlab Accessibility Checker recommends "Partially conformant" until a full manual review has been done. It warns you if you choose "Fully conformant" while your latest audit still finds issues.

= Does MSACAlab Accessibility Checker work on multisite? =

Yes. Activate it for the whole network or on single sites. Each site has its own audits, fixes and settings, and is managed from that site's dashboard.

= What happens if I deactivate MSACAlab Accessibility Checker? =

All quick fixes stop immediately, because they are applied as pages are shown and never written into your theme or content. MSACAlab Accessibility Checker also clears the page cache of popular caching plugins, so visitors don't see old cached copies. Your settings, audits and history are kept: reactivate and everything comes back as it was.

= What happens to my data if I delete the plugin? =

By default, all MSACAlab Accessibility Checker audits and settings are removed. Your accessibility statement page is your content and is kept. You can change this under Accessibility > Settings.

== Screenshots ==

1. Dashboard: your score, how many issues one click can fix, and your next steps.
2. Audit report with a score gauge and charts by severity, WCAG principle and type of fix.
3. Every problem has a "?" that explains why it matters, who it affects and how to fix it.
4. Quick fixes: one-click fixes that MSACAlab Accessibility Checker re-checks and marks "Fixed and verified". Every fix can be undone.
5. Alt text: describe every image from one list. MSACAlab Accessibility Checker updates the Media Library and the pages that already use the image.
6. Before & after: compare any two audits to see what you fixed, what remains and what is new.
7. Show on page: each problem outlined on your live site, with an explanation.
8. Checks inside the block editor every time you save a post or page.
9. Accessibility statement generator that stays up to date with your audits.
10. Download any report or comparison as a PDF.

== Third-party libraries ==

MSACAlab Accessibility Checker bundles axe-core 4.14.0 by Deque Systems (https://github.com/dequelabs/axe-core), licensed under the Mozilla Public License 2.0, which is GPL-compatible. The license is included in assets/vendor/axe-core/LICENSE.

MSACAlab Accessibility Checker's admin screens and PDF reports use the Atkinson Hyperlegible typeface by the Braille Institute of America (https://www.brailleinstitute.org/freefont/), licensed under the SIL Open Font License 1.1, which is GPL-compatible. The license is included in assets/fonts/OFL.txt. The font is bundled with the plugin and only loads on MSACAlab Accessibility Checker's own screens, never on your site's pages.

MSACAlab Accessibility Checker bundles the IBM Equal Access Accessibility Checker engine 4.0.34 (accessibility-checker-engine, https://github.com/IBMa/equal-access), licensed under the Apache License 2.0, which is compatible with GPLv3. The license and notices are included in assets/vendor/ibm-equal-access/.

Both engines run only in the WordPress admin, while you run an audit or check a post. They are never loaded for site visitors.

The audit report links to Deque University rule pages (https://dequeuniversity.com/rules/axe/) for detailed fixing guidance. These links are only opened if you click them; no data is sent.

The free version contains no licensing, tracking or update code. MSACAlab Accessibility Checker Pro is sold separately on https://corea11y.com and is licensed through Freemius (https://freemius.com): terms https://freemius.com/terms/, privacy policy https://freemius.com/privacy/.

== Changelog ==

= 1.0.2 =
* German, French and Spanish translations of the whole plugin, including the report, explanations, PDF reports, emails and the accessibility statement. axe-core's own rule summaries also follow your language. Sites in English are unchanged.
* The Statement screen reminds you to read a translated statement before publishing it.
* If an audit can't be started, administrators now see the database's reason, and a notice explains when another plugin has changed MSACAlab Accessibility Checker's audits table.

= 1.0.1 =
* Quick fixes: fixes your site doesn't need (for example because your theme already has a skip link) now sit in a collapsed "Not needed on your site" group, so they no longer look like something you have to turn on.
* The free version now lists the 29 fixes in MSACAlab Accessibility Checker Pro, with how many problems on your site each one would fix.
* New Help and support box on the Dashboard and Settings, and a short list of other CoreA11y products (can be hidden).
* Plain-language explanations for seven more checks, including text styled to look like a heading.
* The Audits list no longer shows empty rows.

= 1.0.0 =
* 12 quick fixes (was 6). New: titles for embedded videos and maps, hide empty headings and empty paragraphs from screen readers, restore the natural keyboard order, remove clashing shortcut keys, and respect "reduce motion" settings.
* New Alt text screen: add alt text to every image from one list, including copies already placed in pages and posts. Mark images as decorative, undo any save.
* Fixed and verified: after you turn fixes on, MSACAlab Accessibility Checker re-checks the pages where those problems were found and shows, for each fix, whether the problems are gone.
* Multisite: each site's data is removed when the site or the plugin is deleted.
* Before & after explains why only some pages are compared when audits check one page of each type.
* Deleting MSACAlab Accessibility Checker also removes its fix check results.
* One click: the dashboard and latest report show how many issues quick fixes can solve, with a "Fix them now" button that turns them on and checks they worked.
* Fixed: hidden empty paragraphs were still reported after the empty paragraph fix was turned on.
* 14 new checks, including broken skip links and same-page links, form labels hidden or connected to no field, images described only by a tooltip, repeated or very long alt text, underlined text that isn't a link, pages without regions, icon contrast, and embedded videos that need a caption check.
* Contrast is now measured on plain backgrounds the main engine couldn't decide on, and after page animations finish, so faded-in colours aren't reported wrongly.
* The score now reaches 100 only when the automated checks find no WCAG failures, and the manual checklist progress is shown next to it.
* New look: MSACAlab Accessibility Checker screens and PDF reports now use the MSACAlab Accessibility Checker colors and Atkinson Hyperlegible, a typeface designed for readability. Every color pair meets WCAG 2.2 AA contrast.
* Optional MSACAlab Accessibility Checker Pro, with 29 more fixes, colour-contrast fixes you approve, a weekly check, CSV export, network tools and white-label reports.

= 0.5.2 =
* Checks every page at phone width as well as desktop, to catch problems in mobile menus and layouts (can be turned off in Settings).
* New "Needs a human check" list in reports: items the automated checks can't decide on, shown separately and never counted in the score.
* New best-practice suggestions: empty headings, skipped heading levels, missing main heading, empty table headers, repeated alt text, positive tabindex and conflicting roles. Shown as minor and never counted in the score.
* 17 new MSACAlab Accessibility Checker checks for problems the main engine doesn't cover, including vague link text, file links without the file type, links that open new tabs without warning, placeholder-only fields, tables without headers, very small, justified or all-caps text, empty paragraphs and access keys.
* Extended checks: selected rules from the IBM Equal Access engine for form groups, fieldset legends, table headers and keyboard focus. Runs only in the admin during audits (can be turned off in Settings).
* Manual checklist now has 32 checks, covering zoom and reflow, text spacing, time limits, moving and flashing content, page titles, link text, language changes and more.
* Turning a quick fix on or off (or undoing it) now clears the page cache of popular caching plugins, so visitors see the change straight away.
* The quick fixes script is excluded from script delaying and combining in popular optimization plugins, so fixes work for keyboard and screen reader users right away.
* Pages that block framing with X-Frame-Options: DENY can now be scanned by administrators; clearer messages when a page can't be scanned.
* New "Support information" box in Settings to copy into a support request. Nothing is sent automatically.
* The score is now called the MSACAlab Accessibility Checker score, with a clearer explanation.
* Tested with MySQL/MariaDB.
* Tested with Astra, GeneratePress, Kadence, OceanWP, WooCommerce, Elementor, Contact Form 7, Polylang, Yoast SEO, Wordfence, Really Simple Security, Autoptimize, WP Super Cache, W3 Total Cache and LiteSpeed Cache.
* Deactivating MSACAlab Accessibility Checker now also clears page caches, so fixes disappear for visitors straight away.

= 0.5.1 =
* Passes the WordPress.org Plugin Check with no errors or warnings.
* Added a translation template (languages/msacalab-accessibility-checker.pot).
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
