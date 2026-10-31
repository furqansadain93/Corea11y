# CoreA11y for Squarespace: complete handoff (single file)

Self-contained: everything discussed in Frank's main Claude account, copied 29 Oct 2026. Give this whole file to Claude at the start of the new conversation.

---

## Part 1: Squarespace plan

Written 29 Oct 2026 for a new conversation on Frank's other laptop (a different Claude account). This one file holds everything; the parts below are copied in full:
- Part 1: the Squarespace plan (this part).
- Part 2: what the WordPress plugin does (the reference product).
- Part 3: Shopify app build notes. Squarespace should reuse its runner, checks, score, explanations and fixes code. The code lives on Frank's main PC (`C:\Users\admin\Documents\GitHub\corea11y-app`, private GitHub repo furqansadain93/corea11y-app); clone it on this laptop to reuse it.
- Part 4: the 1.1 Guided checks spec (shared by every platform).

### Names, brand, rules
- Product name: **CoreA11y for Squarespace**. The brand and website are CoreA11y (corea11y.com); the company is MSACAlab (Pakistan). The website already has a /squarespace/ page marked "Coming soon", with a waitlist. Its copy says: "Connect your site in your CoreA11y account and paste one code snippet."
- Always:
  - No AI.
  - No overlay toolbar.
  - Every fix can be undone.
  - Never claim compliance. Use precise wording: "has a one-click fix", "automated checks found…", "Automated checks only" under the score.
  - Don't slow the site for visitors.
  - Store only results, never page content.
  - No WAVE/WebAIM branding.
  - Never ask for or store secrets or passwords in chat.
- Prices: WordPress is Free; Pro Personal $19/mo or $179/yr (1 site); Pro Business $49/mo or $449/yr (5 sites, featured); Agency $109/mo or $999/yr (25 sites); 30-day money-back. These are launch prices; they rise for new customers after 1.1. Shopify is different (Shopify Billing, per store): Free; Pro $29/mo or $279/yr; Plus $79/mo or $749/yr; 7-day trial. Squarespace bills through us, so multi-site plans are possible: **Frank decides whether Squarespace follows the WordPress or the Shopify price list** (open question).

### Platform facts (checked 29 Oct 2026, re-check before building)
- **There's no plugin system and no pages/content API.** The Commerce APIs cover orders, products, inventory, transactions, profiles, forms, discounts and webhooks. Content appears only as a webhook event. Fixes and the audit runner have to run from **Code Injection** (site-wide header/footer).
- **Code Injection needs a paid plan:** Core, Plus or Advanced (2025 plans), or the legacy Business, Commerce Basic or Commerce Advanced. It's **not** on Basic or legacy Personal. The app must detect this and say so plainly.
- **Two ways to connect:**
  1. **Extension (OAuth)**, which works for customers on any plan. Apply for OAuth client credentials at account.squarespace.com/developer-apps. Squarespace vets extensions for the Extensions Directory, and providers bill merchants themselves.
  2. **API key**, which needs Commerce Advanced. The merchant makes it under Settings > Advanced > Developer API Keys. Only for single-site custom apps; not our main route.
- **Product image alt text can be written through the API:** `POST /{version}/commerce/products/{productId}/images/{imageId}` with `altText` (max 200 characters). This is the only alt text that can be fixed through the API. Page, blog and gallery images can't.
- Target the 7.1 layout (Fluid Engine) first. The 7.0 templates are legacy; support them only if cheap.
- Sources:
  - [Authentication and permissions](https://developers.squarespace.com/commerce-apis/authentication-and-permissions)
  - [Update product image](https://developers.squarespace.com/commerce-apis/update-product-image)
  - [Developer home](https://developers.squarespace.com/)
  - [Code Injection plans](https://beyondspace.studio/qanda/which-squarespace-plans-include-code-injection-in-2025)
  - [Extensions FAQ](https://forum.squarespace.com/topic/295191-using-squarespace-extensions-faq/)

### Architecture (proposal, confirm with Frank)
- **Hosted web app** at app.corea11y.com with a "CoreA11y account" (sign up / log in; one account can hold several sites up to the plan's site limit). Same stack as the Shopify app (Node, React Router, Postgres), ideally the same backend with a `platform` field, hosted on Fly.io or Render.
- **Connect a site:**
  1. Merchant adds the site URL.
  2. Optionally connects the Squarespace Extension (OAuth), needed for product alt text and site info.
  3. Pastes **one snippet** into Settings > Developer Tools > Code Injection > Header (or Footer).
  4. The app checks the snippet is live.
- **The snippet** is one small `<script async src="https://app.corea11y.com/sq/<siteKey>.js">`:
  - Visitors get only the fixes that are on (no fixes = nothing loaded, or an empty file). Target under 5 KB gzip, cached, no layout shift.
  - The audit runner loads only when the URL carries a signed one-time token, as on Shopify (`?corea11y_audit=<ts.nonce.HMAC>`, 30 min, single use).
  - Privacy note for the policy: visitors' browsers fetch the fixes file from our server, so their IP reaches us. Never log it beyond standard short server logs.
- **Audit:** runs in the merchant's own tab, page by page, as on Shopify. axe-core 4.14.0 (WCAG 2.0/2.1/2.2 A and AA), selected IBM Equal Access rules, and our checks.js. Results are posted to our server.
  - Pages come from sitemap.xml: home, up to 3 products, 2 collections or shop pages, 3 pages and 2 blog posts, plus cart and search. Maximum 12 on the "one page per template" default; "All pages" (capped per plan) as an option.
  - Each page is checked at desktop and phone width. The overlay panel lives in a shadow DOM, is hidden while measuring, and is excluded from axe.
- **Sites without Code Injection (Basic and legacy Personal):** open question. Options:
  1. A server-side scanner (headless Chromium on our server, public pages only), audit-only with no runtime fixes.
  2. Tell them to upgrade.

### Features to build (map of the WordPress product)
Each line says how the feature works on Squarespace. Free means the free plan, Pro means all Pro plans, unless marked.

1. **Audit and score (Free):** the same three layers of checks and "Needs a human check" list. Same score: weights 4/3/2/1, score = 100 − round(2 × points ÷ pages), best-practice rules excluded, capped at 99 while any WCAG failure remains, "Automated checks only" caption. Checklist / review progress shown beside the score, never merged into it.
2. **Report (Free):**
   - Headline and counts; charts by severity, WCAG principle and fix type; pages with the most issues; issues grouped by rule with WCAG criterion, pages and elements.
   - **"?" explanations** with Squarespace-specific how-to-fix steps (e.g. "Edit page > image block > Design > Alt text"). Make an explain-squarespace.js like Shopify's explain.js.
   - **Dismiss / restore** with a reason.
3. **Show on page (Free):** opens the live page with each problem outlined, explained, and next/previous navigation. Uses the snippet plus a signed token, admin only.
4. **Before & after (Free):** compare two audits (fixed, remaining, new), limited to pages in both.
5. **PDF reports:** Free gets the branded PDF; Agency gets white-label (own logo or name, own colour, readable text colour chosen automatically). CSV export on Business and Agency.
6. **Quick fixes, delivered by the snippet at render time, all undoable:**
   - **Free 12:** port the WordPress Lite set. Skip link, visible focus, page language, allow zoom, form field labels, iframe titles, hide empty headings and paragraphs, remove positive tabindex, remove clashing accesskeys, reduced motion. Page titles: check whether Squarespace already handles them; replace any fix that doesn't apply with a Squarespace equivalent.
   - **Pro 29:** port what applies: link and button names, new-tab warning, file type on links, unique "Read more", autocomplete, form field names, main landmark, decorative SVGs, focus not obscured by the sticky header, underline links (look), menu names, header/footer landmarks, keyboard dropdown menus (Squarespace folder menus), stop autoplay (video and gallery autoplay), empty links, image-link names, table headers, required fields, form error messages, target size (look), placeholder contrast (look). Drop WordPress-only ones.
   - **Fix them now** box: counts issues the off fixes would solve, turns them on as one batch, then **Fixed and verified** re-checks the affected pages and rules.
   - **Change history** with per-change undo and "turn all off".
   - "look" fixes change appearance, so they're never in the automatic count.
7. **Alt text screen:**
   - Free: lists every image missing or with bad alt text (file-name alt, "image of", repeats nearby text, very long), grouped by page.
   - Product images (with the OAuth extension): type the alt and save through the API, with an undo log that keeps the old value.
   - Page, blog and gallery images: no API. The row shows the image, the page link and the exact steps; the user answers "I've done this" or "Not needed (decorative)", and the next audit verifies it. This is the "assisted fix" pattern from Frank's MSACA Fixer.
   - Never write alt text automatically: not from file names, not from AI.
8. **Colour contrast queue (Pro):**
   - Groups contrast failures by colour pair and suggests the nearest same-hue colour that passes (target ratio + 0.08). The user approves each group or types their own colour (refused if it fails). Applied per element by the snippet, with undo or undo all.
   - Extra for Squarespace: a "Copy as Custom CSS" button for Design > Custom CSS, so the fix can be made permanent without the snippet.
9. **Accessibility statement (Free):** the same form and conformance status (recommends "Partially conformant"; warns on "Fully conformant" while issues remain). There's no pages API, so give the text to copy into a new Squarespace page, with steps. Optionally offer a hosted statement URL on corea11y.com that stays updated with the latest audit.
10. **Guided checks (1.1 spec, same rules on every platform):**
    - Three answers: **Passes**, **Problem found** (the user picks the severity, serious preselected; it becomes an issue), **Not applicable** (reason required).
    - Answers expire when pages change or after 12 months (6 on Agency). Who and when are stored; PDFs say "Self-reported".
    - Human answers can lower the score, never raise it.
    - Focus order map and screen reader view in Show on page.
    - WCAG criterion table on Pro (CSV and JSON on Business and Agency). Team assignment on Business and Agency.
11. **New automatic checks (1.1 spec):**
    - Free: reflow at 320 px, text spacing, focus visible, focus not obscured, hover-only menus, clickable things not reachable by keyboard, target size, orientation.
    - Pro: 200% text, label in name, motion over 5 s, gradient and image contrast.
12. **Weekly check (Pro):** a server job reads sitemap.xml (and content webhooks if the extension is connected), finds new or changed pages, and emails a summary with a reminder to re-audit. No visitor data.
13. **Multiple sites:** the account dashboard lists every connected site with its score, up to the plan limit (1, 5 or 25). Business and Agency can copy quick-fix settings to all their sites, with undo.
14. **Billing:** Squarespace doesn't bill for extensions, so we bill ourselves at the same prices. Decide between Freemius (SaaS/app licensing) and Stripe/Paddle. Free plan without a card.
15. **Data and removal:** results only. On disconnect: stop the snippet output (an empty file), revoke OAuth, and offer "Delete my data". Account deletion removes everything.

### Not possible on Squarespace (say so in the product)
- Editor checks on save (no editor hooks).
- Writing alt text or content to pages and blogs (no content API); assisted steps instead.
- Fixes on sites without Code Injection.
- Pages behind member areas or logins, and third-party embeds.

### Reuse from the Shopify app (C:\Users\admin\Documents\GitHub\corea11y-app)
- The audit runner (resume after redirects via sessionStorage, overlay in shadow DOM), checks.js, scoring, the opportunity / Fix-now logic, the quick fixes JS (a port of the WordPress fixes.js), the report UI and the explanations structure.
- Ideally move the shared core into one package (`corea11y-core`) that WordPress, Shopify and Squarespace all use, so checks and scores stay identical.

### Suggested build order
1. Account and site connection: snippet, siteKey, a live check of the snippet, and plan detection (does Code Injection work?).
2. Audit runner, report, score and explanations (Squarespace steps).
3. 12 free quick fixes, Fix them now, Fixed and verified, change history.
4. Alt text screen (product images through OAuth; assisted steps for the rest) and the statement.
5. Billing and plan limits, then the Pro fixes, contrast queue, PDF and CSV, weekly check, multiple sites.
6. 1.1 guided checks and new automatic checks (shared with WordPress and Shopify).
7. Extension (OAuth) application and the Extensions Directory listing.

### Testing
- Use a Squarespace trial site on a plan with Code Injection (Core or higher), with 7.1 templates, a store with products, a blog, a gallery, forms and a folder menu.
- Same bar as WordPress: fixture pages for every check (fail and pass), no false positives on real Squarespace templates, axe 0 on our own app screens, keyboard and NVDA run-through.
- Measure visitor impact of the snippet: page load with and without it.

### Open questions for Frank
- [ ] Sites without Code Injection: server-side scanner, or "upgrade to Core" only?
- [ ] Billing: Freemius, Stripe or Paddle?
- [ ] Shared backend with the Shopify app, or a separate app?
- [ ] Apply for the Squarespace Extension (OAuth) now, or launch with snippet only and add product alt text later?
- [ ] Hosted statement URL on corea11y.com, or copy-and-paste only?
- [ ] Prices: WordPress list (multi-site plans) or Shopify list (per site)?

---

## Part 2: What the WordPress plugin does (reference product)


### Names
- Brand and website: **CoreA11y** (corea11y.com). The company is **MSACAlab**.
- WordPress plugin: **MSACAlab Accessibility Checker** (renamed from CoreA11y for WordPress.org; slug `msacalab-accessibility-checker`). Pro = MSACAlab Accessibility Checker Pro. Version 1.0.0, under WordPress.org review.
- Shopify app: **CoreA11y** (Shopify isn't affected by the WordPress.org name issue). Code prefixes in both stay `corea11y`.

### Rules (both platforms)
- No AI. No overlay toolbar. Every fix can be undone. Content never leaves the site/store; only results are stored.
- Never claim compliance. Use precise wording: "has a one-click fix", "automated checks found…", "Automated checks only" under the score, and "found no issues that automated testing can detect".
- Don't slow the site for visitors. Audits run in the admin's browser. No WAVE or WebAIM branding inside the product.
- WordPress prices (Shopify differs, see Part 3): Lite free; Pro Personal $19/mo or $179/yr (1 site); Business $49/$449 (5, featured); Agency $109/$999 (25). These are launch prices; they rise for new customers after 1.1.

### 1. Audit
- Runs in the admin's browser: it opens pages one by one (hidden iframe on WordPress; the merchant's own tab on Shopify) and posts results to the backend.
- Pages: "one page per template" (fast default) or all pages, chosen in Settings. Each page is checked at desktop and phone width.
- Three layers of checks:
  1. axe-core 4.14.0, WCAG 2.0/2.1/2.2 A and AA rules.
  2. Selected IBM Equal Access rules (optional in Settings).
  3. Our own checks (checks.js): vague link text, placeholder-only fields, tables without headers, empty headings and paragraphs, hidden labels, broken skip links, same-page links with no target, orphaned or empty labels, very long, duplicate or tooltip-only alt text, underlined text that isn't a link, pages without landmarks, uninformative titles, invalid longdesc, videos needing a caption check, links with no destination, icon contrast (3:1), and contrast re-measured on plain backgrounds.
- Before measuring, the audit finishes CSS transitions and animations (no false contrast reports from fade-ins).
- Anything the checks can't decide goes under **"Needs a human check"**, never counted as a failure.

### 2. Score and report
- Score 0–100: weights critical 4, serious 3, moderate 2, minor 1; score = 100 − round(2 × points ÷ pages). Best-practice rules are excluded. The score is capped at 99 while any WCAG failure remains. Caption under the ring: "Automated checks only".
- Checklist progress is shown beside the score ("Manual checklist: 0 of 32 reviewed, 0 failed"), never merged into it.
- Report: a headline, counts (issues, types, pages, critical), charts by severity, by WCAG principle and by type of fix (Automatic fix / Fix with approval / Needs your action), pages with the most issues, and issues grouped by rule with WCAG criterion, pages and elements.
- **"?" explanations** on every rule and concept: why it matters, who it affects, how to fix it (WordPress-specific steps; Shopify has its own fix steps in explain.js).
- **Show on page**: opens the live page with each problem outlined, an explanation, and next/previous navigation (admins only, nonce-protected).
- **Dismiss / restore** an issue (with a reason).
- **Before & after**: compare any two audits (fixed, remaining, new), limited to pages checked in both audits.
- **PDF** of any report or comparison: branded (charcoal bar, lime stripe) or white-label on Agency.

### 3. Quick fixes (one-click, all undoable)
- Applied as pages are shown (render time); theme and content files are never rewritten. Deactivating the plugin stops all of them at once, and the page cache is purged on every change.
- **Lite (12):** skip link, visible keyboard focus, page language, allow zoom, labels for search and comment fields, missing page titles, titles for embedded videos and maps (iframes), hide empty headings, hide empty paragraphs, remove positive tabindex, remove clashing accesskeys, respect reduced motion.
- **Pro (29 more):** image alt from the Media Library, link names, button names, new-tab warning, file type and size on links, unique "Read more" links, remove redundant link titles, autocomplete on fields, give every form field a name, main landmark, hide decorative SVGs, focus not hidden by sticky headers, underline links (look), menu names, header/footer landmarks, page heading, keyboard dropdown menus, stop autoplay, merge adjacent duplicate links, empty links, image-link names, page-number links, table headers, image title cleanup, connect form labels, required-field marking, form error messages, target size (look), placeholder contrast (look).
- "look" fixes change appearance, so they're excluded from the automatic "Fix them now" count.
- **Fix them now** box (Dashboard and latest report): "Quick fixes can solve N issues", turns them on as one batch, then verifies. It also shows the locked Pro count ("Pro has a one-click fix for N more issues").
- **Fixed and verified**: after fixes change, the affected pages are re-checked for the affected rules only, and each fix gets a badge.
- **Change history** with per-change undo and "turn all off".

### 4. Fix screens (only a person can decide; the plugin does the rest)
- **Alt text (Lite):** every image used on the site that needs alt (empty Media Library alt, empty or missing alt in content, featured images, non-library images), sorted by usage, 40 per page. Each row has a thumbnail, file, "Used on" links, an alt field and a Decorative checkbox. Saving writes the Media Library alt and the alt in post content (creating a revision), with an undo log that restores the exact original. The alt text stays if the plugin is removed. The plugin never writes alt text itself (not from file names, not from AI).
- **Colour contrast queue (Pro):** groups contrast failures by colour pair and suggests the nearest same-hue colour that passes (target ratio + 0.08). The user approves each group or types their own colour (refused if it fails). Applied per element on that page only, undo or undo all. Never an automatic site-wide recolour.

### 5. Other features
- **Manual checklist:** 32 checks with step-by-step instructions, Pass/Fail per check; progress shows beside the score. Being replaced by **Guided checks** in 1.1 (see Part 4).
- **Accessibility statement generator:** a few fields and a conformance status (recommends "Partially conformant"; warns when "Fully conformant" is chosen while issues remain). Live shortcode `[corea11y_statement]` that updates with the latest audit, a draft page, and an optional footer link. The page belongs to the user and stays if the plugin is removed.
- **Editor checks:** on every post save, the content is checked and problems are listed in the block editor sidebar and in the pre-publish panel (publishing is never blocked).
- **Admin bar:** "Accessibility: N issues" for the current page, linking to Show on page.
- **Compatibility:** purges popular caching plugins on changes and keeps the fixes script from being delayed or combined.
- **Data:** everything is stored in the site's own database. Deleting the plugin removes its data (optional); deactivating keeps it.

### 6. Pro-only features and plan gating
- All Pro plans: the 29 Pro fixes, the colour contrast queue, and the **Weekly check** (WP-Cron; emails a summary of new or edited pages and images without alt, sent by the site itself; reminder when an audit is overdue).
- Business and Agency: CSV export; on multisite, the Network Admin overview (every site's score) and copying quick fixes to every site (with undo and defaults for new sites).
- Agency: white-label PDF (own logo or name, own colour; text colour is picked automatically to stay readable).
- Without an active licence, Pro screens show "Activate your licence"; fixes already applied keep working.

### 7. Admin UI
- Menu label "Accessibility" with these screens: Dashboard, Quick fixes, Alt text, Audits, Before & after, Statement, Manual checklist, Settings (Pro adds Colour contrast).
- Brand bar on every screen, charcoal score card with a lime ring, Atkinson Hyperlegible font, axe 0 on all admin screens. A "User guide" link goes to corea11y.com/docs/wordpress/ (Shopify: /docs/shopify/).

### Matching on Shopify (older status; Part 3 is newer)
- Done: audit runner, the same score and wording, report, the 12 Lite quick fixes (theme app embed), change history with undo, the Fix-now box, explanations with Shopify steps.
- Next: Alt text screen (product media via GraphQL with an undo log), contrast queue, Shopify Billing at the same prices, plan limits, a phone-width pass, then Guided checks from the 1.1 spec (same answers and score rules where Shopify allows).
- Not portable as-is: post revisions, the block editor panel, multisite and WP-Cron (use Shopify webhooks or a scheduled job), and form-plugin integrations.

---

## Part 3: Shopify app notes

The brand stays **CoreA11y** on Shopify. The WordPress plugin was renamed MSACAlab Accessibility Checker for WordPress.org only.

### Working rules with Frank
- Git: Frank commits and pushes with GitHub Desktop (repo corea11y-app, github.com/furqansadain93/corea11y-app, branch main, private). After every update written to his folder and verified, end the message with a "Commit and push" step (GitHub Desktop, ready-to-paste Summary, Commit to main, Push origin). Never commit .env, prisma/dev.sqlite or node_modules.
- Writing files to his PC through the device bridge: device_commit_files can report "written" but leave the old file when a staged path is reused. Always stage into a fresh folder, commit, then stage the files back and compare md5. Only say "it's in your folder" after that check passes.
- `shopify app dev` hot-reloads code; new migrations, scopes, toml changes or new route files need a restart. A grey "sad page" in the admin means `shopify app dev` isn't running.
- React 18 + Polaris web components: onChange never fires on s-switch / s-checkbox; onInput works (useToggle pattern in app/components/ui.jsx).

### UI direction (Frank, 28 Oct)
Same content as WordPress, platform look. Match every WordPress element (greeting, stat cards with "?" explanations, score label, headline, severity colours, charts in cards, pages table, Download PDF) using the platform's own components. CoreA11y branding lives in the PDF, the listing and the icon. (For Squarespace there's no admin UI kit: build our own app UI in the CoreA11y brand at app.corea11y.com.)

### Built on Shopify (reuse for Squarespace)
- **Audit:** the storefront runner (theme app embed) navigates the merchant's own tab page by page (storefronts forbid framing) and runs axe 4.14.0 plus corea11y-checks.js. Results go through the app proxy. The start link is signed, valid 30 min, single use. Pages: home + up to 3 products, 2 collections, 3 pages, 2 blog posts + cart + search (max 12). The panel stays visible (pointer-events:none during the colour follow-up).
- **Phone width:** page HTML is fetched and rendered in a 390×844 srcdoc iframe with `<base>`; axe and checks run there and are merged with desktop like WordPress (page.widths {desktop, phone}, phoneError fallback).
- **Score and wording** identical to WordPress: weights 4/3/2/1, capped 99, best practice excluded, "Automated checks only". Bands: Great ≥90, Good ≥75, Needs work ≥50, Needs attention below (app/lib/report-model.js, shared by screen and PDF). CONCEPTS = the WordPress "?" texts. Fix types: Automatic fix / Fix with approval / Needs your action.
- **Report:** Download PDF and Run a new audit; score ring with band label; headline + next step; 4 stat cards with "?"; Fix-now card; charts (severity, principle, fix type); pages with the most issues; problems with explanations, quick-fix status, alt link, Deque link, per-element Dismiss; Needs a human check; pages checked + width note; dismissed list with Restore; compare link.
- **Dismiss/restore:** issue fingerprint md5(rule|host+path|selector). Dismissal table with reasons false_positive / fixed_elsewhere / accepted. Dismissed issues are excluded from score and counts in every audit.
- **PDF:** signed read-only route (2 h). Print-ready page like WordPress: charcoal header + lime stripe + logo mark + "corea[11]y" wordmark + site name; score card, stats, charts, top pages, every problem (5 instances each), suggestions, human-check table, pages checked, footer "Automated results do not certify WCAG conformance." Comparison PDF too.
- **Before & after:** two selects (earlier = Before); rings, resolved/remaining/new, paired severity bars, change-by-problem table, "What was compared" (pages in only one audit not counted). Matching by fingerprint.
- **Dashboard:**
  - Greeting, Run a new audit + Download PDF, first-time "Get set up" checklist, Fix-now card.
  - Latest audit card; "Since your previous audit" (score change, resolved, new).
  - "Score over time" (last 10 audits); "What to fix next" (top 3); issues by severity.
  - Tool cards; disclaimer line.
- **Quick fixes:** 12 Lite fixes, settings stored per site, history with per-change Undo, turn all off.
- **Alt text:** product images + description images + files; Decorative option with a warning when it would remove existing alt; per-save exact Undo.

### Still to build on Shopify (same list applies to Squarespace)
1. Fixed and verified, and the locked Pro count in the Fix-now card.
2. Show on page.
3. Settings (pages to check, extended IBM checks on/off, phone width on/off).
4. Statement generator; Guided checks (1.1 spec) with progress beside the score.
5. User guide link (corea11y.com/docs/shopify/; Squarespace: /docs/squarespace/), Contact/Support.
6. Pro: 29 Pro fixes, colour contrast queue, weekly check, CSV export, white-label PDF.
7. Billing and plan gating, review prompt, listing.
8. Hosting (Fly.io or Render) + Postgres.

### Shopify pricing (Frank, 28 Oct)
- Shopify Billing only, per store: Free $0; Pro $29/mo or $279/yr; Plus $79/mo or $749/yr; 7-day trial on Pro and Plus. "Agency" became "Plus" because the merchant pays, not the agency.
- Plan contents:
  - Free: 12-page audits, every check, phone width, report, explanations, the 12 free fixes, alt text, Before & after, PDF, statement, guided checks.
  - Pro: up to 50 pages, plus the 29 Pro fixes, colour contrast queue and a weekly reminder.
  - Plus: every page, an automatic weekly check, CSV export, white-label PDF and priority support.
- **Gating rule (applies everywhere):** never lock anything that tells the merchant the truth about their site (all checks, phone width, explanations, the human-check list). Lock time-savers (Pro fixes, contrast queue), scale (pages, automatic weekly check) and agency needs (CSV, white label).

### Competitive positioning (Shopify, 28 Oct; same story for Squarespace)
- Market: toolbar widgets $5–49/mo, overlays $49–500/mo (UserWay, accessiBe), code-fixing scanners $200–600/mo.
- Position: between widgets and the $200+ scanners. A real WCAG audit with plain-language explanations, fixes that can be undone, an alt text editor, no overlay or toolbar, no compliance claims. The FTC's April 2025 order against accessiBe (unsupported WCAG-compliance claims) supports the honest wording; use it as context, never as an attack.
- Listing message: "Find and fix accessibility problems without an overlay. Fixes you can undo. Doesn't slow your site. Tells you what still needs a person." Never "compliant", "lawsuit-proof" or "100% accessible".
- Reviews: generous free plan; ask for a review only after a verified improvement.

---

## Part 4: 1.1 Guided checks spec (all platforms)

2026-10-28 · Frank

### Summary

Version 1.1 turns the 32-step Manual checklist into **Guided checks**: the plugin tests what it can, highlights the exact elements for the rest, and asks the site owner one question at a time. Problems found by a person become real issues in the same report as automated ones.

What changes for users:

- About 12 new automatic checks for things that today need a person (reflow, text spacing, focus visibility, hover-only menus and more).
- More "fix it in one place" screens like Alt text: link text, form labels and video captions.
- Guided checks with three answers: **Passes**, **Problem found**, **Not applicable**.
- A second number beside the score: how much a person has reviewed. Pro adds a WCAG criterion table built from those answers.

Principles (unchanged from 1.0):

- No AI. Every check is a rule a person can read.
- The score is honest: human answers can lower it, never raise it.
- Nothing is submitted, bought or sent. The plugin never submits forms or changes content without the user's action, and every fix can be undone.
- Never claim compliance. Wording says "automated and assisted checks found…", never "fully tested".

### New automatic checks

Each check runs in the existing audit iframe, after axe-core and our own checks, on the pages the audit already opens. Results feed the same report, score and quick-fix links. Checks that can't decide report "Needs a human check" and feed the matching guided check.

| Check | WCAG | How it's detected | Plan |
| --- | --- | --- | --- |
| Content reflows at 320 px | 1.4.10 | Resize the iframe to 320 CSS px, find elements wider than the viewport that cause sideways scroll (tables, code, maps excluded) | Lite |
| Text spacing doesn't clip | 1.4.12 | Inject line-height 1.5, paragraph spacing 2, letter 0.12, word 0.16; flag text boxes whose content now overflows with overflow hidden | Lite |
| Text resizes to 200% | 1.4.4 | Double the root font size; flag clipped or overlapping text | Pro |
| Focus is visible | 2.4.7 | Focus each tabbable element; compare outline, box-shadow, border, background, text decoration before and after; no change = flag | Lite |
| Focus isn't hidden | 2.4.11 | After focus, check the element's box against sticky and fixed elements (elementsFromPoint) | Lite |
| Menus open by keyboard | 2.1.1 | Find submenus revealed only by a :hover rule with no :focus-within or JS toggle | Lite |
| Clickable things reachable by keyboard | 2.1.1 | Elements with cursor:pointer, onclick or role=button that aren't focusable | Lite |
| Visible label is in the accessible name | 2.5.3 | Compare the visible text with the computed name of links, buttons and fields | Pro |
| Target size | 2.5.8 | axe target-size rule switched on, plus our own spacing check | Lite |
| Moving content can be paused | 2.2.2 | Watch the page for 6 seconds (MutationObserver plus animations longer than 5 s); flag sliders, marquees and infinite animations with no pause control nearby | Pro |
| Orientation isn't locked | 1.3.4 | Look for CSS or manifest rules that force one orientation | Lite |
| Text over gradients and images | 1.4.3 | Parse CSS gradient stops, and sample same-site background images under each text box on a canvas; report the worst point | Pro |

All checks are read-only: they measure the page and never change it for visitors.

### Assisted fix screens

When only a person can decide the fix, the plugin collects every case into one screen, like Alt text in 1.0. The user types the answer once and the plugin writes it everywhere it belongs, with undo.

| Screen | What it lists | What saving does | Plan |
| --- | --- | --- | --- |
| Alt text (1.0, extended) | Images with no alt, plus new flags: file-name alt, "image of", alt repeating nearby text, very long alt | Writes Media Library alt and alt in content (existing log and undo) | Lite |
| Link text | Links named "Read more", "Click here", "Learn more" with no context, and links whose name is only a URL | Writes the new text into post content, or an aria-label when the visible text must stay | Pro |
| Form labels | Fields with no label or placeholder-only labels, grouped by form (core search, comments, Contact Form 7, WPForms, Gravity Forms) | Writes the label into the form's settings where the form plugin allows it; otherwise shows the exact steps | Pro |
| Video captions | Embedded and uploaded videos with no caption track | Uploads a .vtt file to the Media Library and links it to the video block; YouTube/Vimeo get steps for their own caption settings | Pro |

Each row shows a thumbnail or snippet, "Used on" links and a Mark as fine option. Saves go through revisions and the screen's own undo log, like Alt text today.

### Guided checks

Guided checks replace the Manual checklist screen. Each check opens the live page with the relevant elements outlined (the existing Show-on-page highlighter), gives short steps, and asks for one of three answers:

- **Passes**: it works. No note needed.
- **Problem found**: a one-line note. This creates an issue in the latest audit (rule manual-<check id>, the WCAG criterion, the page, the note) with how-to-fix text.
- **Not applicable**: a required one-line reason, e.g. "No videos on this site".

The plugin pre-answers nothing. It only narrows the work: if an automatic check already failed, the guided check shows "Already failing, fix first"; if the site has no videos, the media checks start with "Not applicable?" suggested but not set.

| Guided check | What the plugin prepares | WCAG |
| --- | --- | --- |
| Keyboard walk-through | Focus order map: numbered badges in Tab order on the page, big jumps flagged | 2.1.1, 2.1.2, 2.4.3 |
| Menus, modals and widgets | List of menus, dialogs, tabs and sliders found, each opened in turn | 2.1.1, 2.1.2, 4.1.2 |
| Screen reader view | Text outline per page: headings, landmarks, link and button names, image alts, in reading order | 1.3.1, 2.4.4, 2.4.6 |
| Status messages | Forms and carts found, with the steps to trigger a message and listen for it | 4.1.3, 3.3.1 |
| Reflow and text spacing | Side-by-side snapshots at 320 px and with spacing applied, automatic findings marked | 1.4.10, 1.4.12 |
| Colour alone | Links, required fields, charts and status badges found | 1.4.1 |
| Text over images | Text boxes over photos, video or busy backgrounds the contrast check couldn't measure | 1.4.3 |
| Alt text quality | Every image beside its alt, one answer per image or for all | 1.1.1 |
| Captions and transcripts | Videos and audio found, caption tracks listed | 1.2.1 to 1.2.5 |
| Error messages | Forms found, with steps to try empty and wrong input (the user submits, never the plugin) | 3.3.1, 3.3.3 |
| Login and checkout | CAPTCHA and login forms found | 3.3.7, 3.3.8, 3.3.4 |
| Consistency | Navigation and help links compared across audited pages, differences listed | 3.2.3, 3.2.4, 3.2.6 |
| Sensory instructions | Phrases like "click the green button" or "on the right" found in content | 1.3.3 |
| Timeouts and flashing | Session timeouts and fast-flashing animations found | 2.2.1, 2.3.1 |

The final list keeps the 32 existing checklist steps mapped into these checks, so 1.0 answers carry over.

### Score and reporting

Human answers can lower the score but never raise it, so nobody can click their way to 100.

| Answer | Score | Review progress | Report |
| --- | --- | --- | --- |
| Problem found | Counts like a WCAG failure of the severity the user picks (critical, serious, moderate or minor; serious preselected); score capped at 99 | Counts as reviewed | Listed under "Problems found by a person" |
| Passes | No change | Counts as reviewed | Review log |
| Not applicable | No change | Counts as reviewed | Review log, with the reason |
| No answer | No change | Not reviewed | "Not reviewed yet" list |

Rules:

1. **Two numbers, always together.** The score ring keeps "Automated checks only" plus problems found by a person. Beside it: "Reviewed by a person: 24 of 32 checks, 3 problems found". Never one combined number.
2. **Answers expire.** Each answer stores the pages and elements it covered, who answered, and the date. It goes back to "Needs checking" when a covered page changes (post modified), a new audit finds new elements of that kind (a new menu, form or video), or the review period passes: 12 months on Lite and Pro Personal and Business, 6 months on Agency.
3. **Who and when.** Every answer stores the WordPress user and the date. PDFs say "Self-reported by Jane Doe on 28 Oct 2026".
4. **Problems found are fixed like any issue.** Fixing one and answering Passes on the next review closes it; the Before & after comparison shows it.

Reports:

- **PDF (Lite and Pro):** three parts: automated results, problems found by a person, and the review log. The cover line reads "Automated and assisted checks. Not a certification of WCAG conformance."
- **WCAG criterion table (Pro):** each A and AA criterion with Supports, Partially supports, Does not support, Not applicable or Not evaluated, built from automated results plus answers. Statement and VPAT wording, labelled self-reported. Exported in the PDF and as CSV (Business+).
- **Accessibility statement:** the generator can add "Last reviewed by a person on [date]" and known issues from Problem found answers.

### Lite vs Pro

Lite gets every guided check and the honest two-number view, so the free plugin stays useful on its own. Pro gets the screens that save the most time and the reports agencies sell.

| Feature | Lite | Pro Personal | Pro Business | Agency |
| --- | --- | --- | --- | --- |
| 8 new automatic checks (reflow, spacing, focus visible and hidden, hover menus, keyboard reach, target size, orientation) | Yes | Yes | Yes | Yes |
| 4 advanced checks (200% text, label in name, motion, gradient and image contrast) | No | Yes | Yes | Yes |
| Guided checks with three answers, review progress, expiry | Yes | Yes | Yes | Yes |
| Focus order map and screen reader view | Yes | Yes | Yes | Yes |
| Link text, form label and caption screens | No | Yes | Yes | Yes |
| Review log and problems found in the PDF | Yes | Yes | Yes | Yes |
| WCAG criterion table (PDF) | No | Yes | Yes | Yes |
| WCAG criterion table (CSV) | No | No | Yes | Yes |
| Assign checks to team members, reminder when answers expire | No | No | Yes | Yes |
| White-label review report | No | No | No | Yes |

### Competitors and pricing

Guided manual testing exists only in enterprise tools and developer extensions; no WordPress plugin offers it, and none pairs it with one-place fix screens. As of 28 Oct 2026:

| Product | Guided manual testing | Price (1 site or 1 user) | Difference from us |
| --- | --- | --- | --- |
| Deque axe DevTools Pro | Intelligent Guided Tests: keyboard, interactive elements, modals, images, structure; newer version answers with AI | $45 per user per month | Developer tool, AI-driven, no fixes |
| Microsoft Accessibility Insights | Assessment: 24 tests, Pass/Fail, HTML and JSON report by success criterion | Free | Developer extension, page by page, no score, no fixes |
| Siteimprove | Guided questions on potential issues; a No becomes an issue; who and when shown | Enterprise | Enterprise only, no fixes |
| Pope Tech | AIM Score guided manual test, merged with automated results into one score | From $225/mo (annual) | Merges human answers into the score |
| Equalize Digital Accessibility Checker | "Needs Review" and dismiss; no guided checks | $190/yr; 5 sites $750/yr; 25 sites $2,250/yr | Our closest WordPress competitor |

What only we do: guided checks next to fix screens with undo; human answers lower the score but never raise it; answers expire when pages change; no AI and nothing leaves the site.

Borrowed for 1.1:

- From Siteimprove: a Passes answer can point to its evidence (for example where the transcript is), stored with the answer.
- From Accessibility Insights: export answers grouped by WCAG success criterion, as CSV and JSON (Business+).
- From Pope Tech: review progress counts as meaningful only once at least 4 page types are checked; until then it reads "Review started".

Pricing decision (Frank, 28 Oct 2026): launch at today's prices to win customers (Personal $19/mo or $179/yr, Business $49/$449, Agency $109/$999). Raise prices for new customers after 1.1 ships; existing customers keep their price.

### Wording and out of scope

Wording rules:

| Say | Never say |
| --- | --- |
| Automated and assisted checks found 4 issues | Your site is accessible / compliant |
| Reviewed by a person: 24 of 32 checks | Fully tested / 100% reviewed means compliant |
| Self-reported by Jane Doe on 28 Oct 2026 | Verified / certified |
| Problem found by a person | Manual failure (sounds like the person failed) |
| Needs checking again: this page changed | Expired / invalid |

Out of scope for 1.1:

- AI of any kind, including alt text suggestions and judging error messages.
- Submitting forms, logging in, adding to cart or any action with side effects. The user does these; the plugin only gives the steps.
- Pages behind a login, third-party embeds from other sites (booking, chat, payment, maps) and PDF documents. These are listed as "Not checked" with a short reason.
- Real screen reader output. The screen reader view shows what the code says, not how NVDA or VoiceOver reads it.
- Pro 1.2 items: PDF and readability checks, optional cloud scanner.

### Build order, testing and open questions

Build starts after WordPress.org approves 1.0.0 and it launches. Each step ships on its own.

1. **Automatic checks** (Lite 8, then Pro 4), with test pages for each and no false positives on the Divi, Astra, Kadence, GeneratePress and OceanWP test sites.
2. **Guided checks screen**: three answers, issues from Problem found, review progress beside the score, expiry, migration of 1.0 checklist answers, assigning checks to team members (Business+).
3. **Focus order map and screen reader view** in the Show-on-page highlighter.
4. **Reports**: review log and problems found in the PDF; WCAG criterion table (Pro), CSV (Business+).
5. **Assisted fix screens** (Pro): link text, then form labels, then captions.

Testing:

- Each new check gets a fixture page that fails and one that passes, plus a run on 8 real pages, as with the 1.0 checks.
- axe 0 on every new admin screen; keyboard-only and NVDA run-through of the guided checks screen.
- Speed: audits get slower (the 320 px pass and the 6-second motion watch). Target: no more than 30% longer per page; motion watch on one page per template only.
- Plugin Check clean; every write undoable; nothing changes for visitors.

Decisions (Frank, 28 Oct 2026):

- **Version:** everything ships together as 1.1 for Lite and Pro, the same version for both as Freemius requires.
- **Severity:** the user picks the severity of each Problem found; serious is preselected.
- **Review period:** 12 months on Lite, Pro Personal and Pro Business; 6 months on Agency.
- **Team assignment:** in 1.1, Business and Agency.
- **Shopify:** the app gets the same guided checks, answers and score rules where the platform allows; checks that need WordPress (post revisions, form plugins) get Shopify equivalents or are left out.

Competitor sources: https://deque.com/?p=37102 · https://accessibilityinsights.io/docs/web/getstarted/assessment/ · https://help.siteimprove.com/support/solutions/articles/80000448552 · https://pope.tech/pricing · https://equalizedigital.com/accessibility-checker/pricing/
