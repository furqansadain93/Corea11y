# CoreA11y user guide

Last updated October 21, 2026. The latest version is at https://corea11y.com/docs/wordpress/.

## What CoreA11y does

CoreA11y finds accessibility problems on your WordPress site, fixes the common ones in one click, and shows you what's left. It checks your site against WCAG 2.2 A and AA, the standard most accessibility laws point to.

- **Find:** audits check every page as visitors see it, at desktop and phone width, and explain each problem in plain language.
- **Fix:** quick fixes solve common problems site-wide with one click, and the Alt text screen lets you describe every image from one list. Every change can be undone.
- **Prove:** CoreA11y re-checks pages after a fix and marks it "Fixed and verified". Before & after comparisons, PDF reports and a live accessibility statement show your progress.

What CoreA11y doesn't do:

- **No overlay.** It adds no toolbar or widget for visitors. Fixes change the page your visitors get, not a layer on top of it.
- **No AI.** It never writes alt text or content for you, and never sends your content anywhere.
- **No compliance promise.** Automated checks find many common problems, but not all of them. CoreA11y helps you find and fix issues and lowers your risk; it does not certify that your site meets WCAG or any law.

## Getting started

Install CoreA11y, run one audit, and you'll know what to fix first within a few minutes.

1. In WordPress, go to **Plugins → Add New Plugin**, search for **CoreA11y**, click **Install Now**, then **Activate**. CoreA11y Pro is installed from the zip in your purchase email (**Upload Plugin**); it replaces the free version and keeps your data.
2. Open **CoreA11y → Dashboard** and click **Run my first audit**. Keep the tab open while it runs; most sites take about a minute.
3. Read the report. Start with **Critical** problems: they block some visitors completely.

The workflow that gets results:

1. **Audit** to find the problems.
2. **Turn on quick fixes** for the problems they solve (CoreA11y → Quick fixes).
3. **Add alt text** to your images (CoreA11y → Alt text).
4. **Fix the rest** in your content and theme, using the "?" explanation on each problem.
5. **Work through the manual checklist** for what software can't check.
6. **Audit again** and compare in Before & after.

The CoreA11y menu:

| Screen | What it's for |
| --- | --- |
| Dashboard | Start audits, see your latest score and checklist progress |
| Quick fixes | One-click fixes, change history and undo |
| Alt text | Describe every image missing alt text from one list |
| Colour contrast (Pro) | Approve readable colours for hard-to-read text |
| Audits | Every audit and its full report |
| Before & after | Compare two audits: fixed, still there and new |
| Statement | Create your accessibility statement page |
| Manual checklist | 32 checks you do yourself |
| Settings | What to scan, extra checks, data and support information |

## Audits and reports

An audit opens each page in a hidden frame in your browser, runs three layers of checks on the page as visitors get it, and saves the results on your own site.

- **Three layers of checks:** axe-core (the most widely used open-source engine), selected IBM Equal Access rules, and CoreA11y's own checks for things like vague link text, placeholder-only fields and tables without headers.
- **Desktop and phone width:** every page is checked at 1280 px and 390 px, so problems in mobile menus are found too.
- **CoreA11y score (0–100):** a summary of the problems found, weighted by severity. It moves when you fix things; it is not a compliance grade, and other tools score differently. It covers automated checks only: 100 means the automated checks found no WCAG failures. Best-practice suggestions, "Needs a human check" items and the manual checklist aren't part of it, so the dashboard shows your checklist progress next to the score.
- **Severity:** Critical, Serious, Moderate and Minor. Fix Critical first: those block some visitors completely.

In a report, each problem shows:

- **"?" explanation** of why it matters, who it affects and how to fix it, in plain words.
- **Show on page:** opens the page with the element outlined, with next/previous to step through each one.
- **One-click fix available** when a quick fix solves it, and a link to the Alt text screen for missing alt text.
- **Dismiss:** hide an issue that isn't a real problem or that you accept for now, with a reason and a note. Dismissals carry over to future audits; restore them at any time.

Two lists are shown separately and never count in the score:

- **Needs a human check:** items the automated checks can't decide on, such as whether a video's captions are accurate.
- **Best practice:** worthwhile improvements that aren't WCAG failures, such as skipped heading levels.

Download any report as a PDF from **Download PDF** (choose "Save as PDF" in the print window).

## Quick fixes

Quick fixes solve common problems across your whole site with one switch: CoreA11y Lite includes 12, and CoreA11y Pro adds 29 more.

How to use them:

1. Go to **CoreA11y → Quick fixes**. Fixes that solve problems from your latest audit show **"Fixes N issues in your latest audit"**: start there.
2. Switch fixes on and click **Save fixes**.
3. CoreA11y re-checks the pages where those problems were found, then marks each fix **Fixed and verified**, **Partly fixed**, or **needs more than this fix** (the rest is up to your content or theme).
4. Click **Verify with a new audit** to update your score.

How they work: fixes are applied as each page is shown. Your theme files and content are never rewritten, so turning a fix off, or deactivating CoreA11y, restores your site exactly. Fixes marked **changes how your site looks** alter the design; check a few pages after turning them on.

Undo: click **Undo** next to any fix, undo everything you just saved from the green message, or undo any line in the change history. CoreA11y clears the page cache of popular caching plugins whenever fixes change. If your host or a CDN such as Cloudflare caches pages, clear that cache too.

The 12 fixes in CoreA11y Lite:

| Fix | What it does |
| --- | --- |
| Skip to content link | Lets keyboard users jump past the menu |
| Keyboard focus outline | Shows clearly where keyboard focus is |
| Page language | Tells screen readers which language to speak |
| Allow pinch-zoom | Lets phone visitors zoom in |
| Search and comment labels | Names WordPress search and comment fields |
| Missing page title | Adds a page title when the theme leaves it out |
| Embedded video and map titles | Names YouTube, Vimeo, map and social embeds |
| Hide empty headings | Screen readers skip headings with no text |
| Hide empty paragraphs | Screen readers skip blank spacing paragraphs |
| Natural keyboard order | Removes forced tab order |
| No clashing shortcut keys | Removes access keys that clash with screen readers |
| Respect "reduce motion" | Turns off animations for visitors who ask for less motion |

The 29 Pro fixes are listed under CoreA11y Pro below.

## Alt text

The Alt text screen lists every image on your site that has no alt text, so you can describe them all from one place instead of opening each image.

1. Go to **CoreA11y → Alt text**. Images used on the most pages come first; each row shows a thumbnail, the file name and the pages that use it.
2. Type a short description in **Alt text**, or tick **Decorative** for images that are only decoration (backgrounds, dividers).
3. Click **Save alt text**. Empty rows are left unchanged.

What saving does:

- Updates the image in the **Media Library**, so it has alt text wherever you use it next.
- Adds the alt text to the **pages and posts where the image is already placed**. WordPress keeps those copies separately, which is why fixing the Media Library alone doesn't fix existing pages.
- Covers featured images and images that aren't in the Media Library.

This is a **permanent fix**: the alt text becomes part of your site and stays even if you remove CoreA11y. Changed pages also get a normal WordPress revision. To reverse a save, click **Undo** in the message, or later under **Recent changes**.

Writing good alt text:

- Describe what matters about the image here, in a short sentence (usually under 125 characters).
- Don't start with "Image of": screen readers already say it's an image.
- For a logo, write the company name. For a linked image, say where the link goes.
- If the image contains text, include that text.

CoreA11y never writes alt text for you. Only you know what an image means on your page.

Use **All Media Library images without alt text** at the top to see images that aren't on a published page yet.

## Tracking progress and proving the work

### Before & after

Compare any two audits to see what was fixed, what remains and what is new, by problem and by page. Download the comparison as a PDF to share with your team or client.

Only pages scanned in **both** audits are compared, so a page isn't counted as fixed just because it wasn't checked the second time. With the default setting (one page of each type), that may be only a few pages. To compare every page, choose **All published pages and posts** in Settings before running both audits.

### Manual checklist

Some things only a person can judge, such as whether the whole site works with only a keyboard, whether captions are accurate, or whether text is easy to understand. **CoreA11y → Manual checklist** walks you through 32 checks with step-by-step instructions. Mark each one passed or failed and add a note. Checklist results don't change the score; they show that the human checks were done too.

### Accessibility statement

**CoreA11y → Statement** builds an accessibility statement following W3C guidance: your commitment, the standard you aim for, known issues and how visitors can contact you. CoreA11y can create the page for you and add a footer link. The statement updates itself with the date of your latest audit. The statement page is your content, so it stays if you remove CoreA11y.

### Checks in the block editor

Every time you save a post or page, CoreA11y checks its content and lists any problems in the editor sidebar and in the pre-publish panel, with the same "?" explanations. Writers fix problems before they go live.

## Settings

**CoreA11y → Settings** controls what audits check and what happens to your data.

| Setting | Options | Default |
| --- | --- | --- |
| Pages to scan | One page per template (fastest) · All published pages and posts (up to 500) · Only the URLs I list | One page per template |
| Check at phone width | Also check each page at 390 px wide | On |
| Extended checks | Add selected IBM Equal Access rules to audits | On |
| Delete data when CoreA11y is deleted | Remove audits, settings and history on delete | On |

**Support information:** a box at the bottom of Settings describes your setup (WordPress, PHP, theme, caching plugins). Copy it into a support request. Nothing is sent automatically.

CoreA11y Pro adds two sections here: **Weekly check** and **White-label PDF reports** (see CoreA11y Pro).

## CoreA11y Pro

CoreA11y Pro is the full plugin with 29 more quick fixes, colour-contrast fixes you approve, and tools for monitoring, reporting and managing many sites.

| Plan | Sites | Monthly | Yearly | Adds |
| --- | --- | --- | --- | --- |
| Pro Personal | 1 | $19 | $179 | 29 extra fixes, colour-contrast fixes you approve, weekly check, priority email support |
| Pro Business | 5 | $49 | $449 | Everything in Personal, CSV export, multisite network tools |
| Agency | 25 | $109 | $999 | Everything in Business, white-label PDF reports |

Every plan has a 30-day money-back guarantee.

### Installing and activating

1. Download CoreA11y Pro from the link in your purchase email.
2. Go to **Plugins → Add New Plugin → Upload Plugin**, choose the zip, then **Install Now** and **Activate**. CoreA11y Lite switches off automatically; your audits, fixes and history carry over.
3. Enter your license key when asked. If your email is already known to Freemius (our payment provider), you may get one confirmation email; click its link to finish.

If your license expires, nothing breaks: fixes already on keep working and can still be turned off or undone. Turning on new Pro fixes, the weekly check, CSV export and updates wait for renewal.

### The 29 Pro fixes

- **Links:** name icon-only links, warn when links open a new tab, say the file type (PDF, Word, Excel), make "Read more" links specific, remove tooltips that repeat link text, combine duplicate image and text links, skip empty links, name image links from your own site, say "Page 2" on page numbers.
- **Buttons and menus:** name icon-only buttons, name your menus, open dropdown menus with the keyboard, hide decorative icons in links and buttons.
- **Page structure:** mark the main content, mark the header and footer, add a main heading where a page has none, connect table headers to their cells.
- **Images and media:** use Media Library alt text in posts, remove tooltips that repeat alt text, stop sound from playing automatically.
- **Forms:** help browsers fill in forms, give every field a name (including labels your theme hides), connect form labels to their fields, announce required fields, read out form messages.
- **Keyboard and focus:** keep focused items out from under sticky headers.
- **Changes how your site looks:** underline links in text, bigger tap targets for small icon links, readable placeholder text.

### Colour contrast

Low-contrast text is the most common accessibility problem, and no automatic fix can safely recolour a whole site. **CoreA11y → Colour contrast** lists every piece of text from your latest audit that is too hard to read, with a suggested colour for each.

1. Compare **Now** and **Suggested**. The suggestion keeps the same colour family and only makes it lighter or darker, just enough to pass WCAG 2.2 AA (4.5:1 for normal text, 3:1 for large text).
2. Tick **Use this colour** on the ones you like, or type your own colour. CoreA11y only accepts a colour that passes; otherwise it uses the suggestion.
3. Click **Apply selected colours**, then run a new audit to see your updated score.

An approved colour is applied only to that element, on the page where it was found, as the page is shown. Your theme and content are not edited, so **Undo** (or **Undo all colour changes**) restores everything exactly. Text over images or gradients isn't listed: check those by eye under "Needs a human check" in the report. For a permanent fix across the whole site, also change the colour in your theme settings.

### Weekly check

Once a week, CoreA11y looks at what changed since your last audit (new or edited pages, images without alt text) and emails a short summary with a **Run this week's audit** link. A reminder also appears on your WordPress dashboard. It runs on your own server; only the email is sent. Turn it on or off and choose who gets it in **Settings → Weekly check**.

### CSV export (Business and Agency)

On any audit report, **Download CSV** gives a spreadsheet with one row per issue: page, problem, WCAG criterion, severity, element, who it affects and how to fix it.

### White-label PDF reports (Agency)

In **Settings → White-label PDF reports**, add your company name, logo, accent colour and a footer line. PDF reports then carry your logo at the top, a stripe and score card in your colour, and your footer line. Any colour works: CoreA11y picks dark or white text on it so the report stays readable. The note that automated results do not certify WCAG conformance is always kept.

### Multisite network tools (Business and Agency)

In **Network Admin → CoreA11y**, see every site's latest audit, score and fixes in one table, and copy one site's quick fixes to every site, with an option to apply them to sites created later. Each site logs the change, so it can be undone there or with **Undo the last copy on every site**.

## Updates, removal, privacy and speed

| If you… | What happens |
| --- | --- |
| Don't update | Everything keeps working, but you miss new fixes and checks, compatibility fixes and security updates. Turn on auto-updates in **Plugins** to stay current. |
| Deactivate | Quick fixes stop at once and your site looks exactly as before. Page caches are cleared. Settings, audits and history are kept for when you reactivate. |
| Delete | Same as deactivating, plus CoreA11y's audits, settings and history are removed (unless you turned that off in Settings). On multisite, every site is cleaned. |
| Let a Pro license expire | Fixes already on keep working and can be undone. New Pro fixes, the weekly check, CSV export and updates wait for renewal. |

What always stays, because it is your content: alt text you added on the Alt text screen or in the Media Library, your accessibility statement page, and any edits you made to pages.

**Privacy:** audits run in your own browser and results are stored in your own database. CoreA11y never sends your content to an outside service. The free version includes the Freemius SDK in anonymous mode: no opt-in screen and no data sent; Freemius is only contacted if you open the Upgrade page or buy and activate Pro.

**Speed:** the checking engines load only in your admin while an audit runs, never for visitors. Visitors only get the fixes you turn on: under 2 KB of script in Lite, about 5.5 KB more when Pro fixes are on, and under 1 KB of CSS.

## Troubleshooting and common questions

**I turned fixes on but nothing changed for visitors.** Clear your host's or CDN's cache (for example Cloudflare). CoreA11y clears popular caching plugins itself, but not caches outside WordPress.

**My score didn't change after turning fixes on.** The score updates when you run a new audit. Click **Verify with a new audit**.

**Another tool (such as WAVE or Lighthouse) gives a different score.** Every tool checks different things and weighs them differently. The biggest gap is usually text over images or gradients: CoreA11y lists it under "Needs a human check" because its contrast can't be measured reliably, while some tools count it as errors. Fix it in your theme or page builder, for example with a darker overlay behind the text. Text on plain backgrounds can be fixed on the Colour contrast screen (Pro). Also make sure both tools checked the same page after the same changes, and clear your page cache first.

**A fix says "needs more than this fix".** The fix did its part, but some of those problems come from your content or theme. Open the report, use the "?" explanation and **Show on page** to fix the rest.

**Only a few pages were compared in Before & after.** Your audits check one page of each type. Choose **All published pages and posts** in Settings and run two audits.

**A page couldn't be scanned.** Usually a security setting stops pages from being shown in a frame, or the page redirects. CoreA11y relaxes the common X-Frame-Options setting for administrators; for stricter rules, ask your host to allow same-origin framing. Very slow pages (over 30 seconds) can also time out.

**A fix changed how my site looks.** Click **Undo** next to it on the Quick fixes screen. Fixes that change the look are marked.

**Does CoreA11y make my site legally compliant?** No software can promise that. CoreA11y finds and fixes many common issues and guides you through the rest, which lowers your risk. For legal questions, speak to a qualified lawyer.

**Getting help:** free users can ask in the WordPress.org support forum; Pro customers get priority email support from **CoreA11y → Contact Us**. Include the **Support information** from Settings.
