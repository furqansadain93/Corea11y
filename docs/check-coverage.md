# CoreA11y check coverage (internal)

Internal reference, not shipped in the plugin. Compares CoreA11y's automated checks with the item list of a widely used free evaluation tool, so we can explain every difference to customers. Last reviewed 21 October 2026, CoreA11y 1.0.0, axe-core 4.14.0.

CoreA11y runs three layers: axe-core (all WCAG 2.0–2.2 A/AA rules plus a short list of best-practice rules), CoreA11y's own checks (`assets/js/checks.js`), and selected IBM Equal Access rules (when "Extended checks" is on). Items software can't decide are listed under "Needs a human check" and never counted in the score.

Status: **Same** = found the same way. **Similar** = found, but the rule differs slightly. **Review** = listed under "Needs a human check". **Not checked** = deliberately left out, with the reason.

## Errors

| Item | CoreA11y rule | Status |
|---|---|---|
| Missing alternative text | `image-alt` | Same |
| Linked image missing alternative text | `link-name`, `image-alt` | Same |
| Spacer image missing alternative text | `image-alt` | Same |
| Image button missing alternative text | `input-image-alt` | Same |
| Image map area missing alternative text | `area-alt` | Same |
| Image map missing alternative text | `image-alt` | Same |
| Invalid longdesc | `corea11y-longdesc-invalid` | Same (added 1.0.0) |
| Missing form label | `label`, `select-name`, `corea11y-hidden-label` | Same. Labels hidden with `display:none` are reported since 1.0.0; Pro fixes them. |
| Empty form label | `label` (when the field has no other name), `corea11y-label-empty` | Same (added 1.0.0) |
| Multiple form labels | `form-field-multiple-labels` | Same |
| Broken ARIA reference | `aria-valid-attr-value` | Same |
| Broken ARIA menu | `aria-required-children` | Same |
| Missing or uninformative page title | `document-title`, `corea11y-title-uninformative` | Similar: "uninformative" means a generic title such as "Untitled" or "New page". |
| Language missing or invalid | `html-has-lang`, `html-lang-valid` | Same |
| Page refreshes or redirects | `meta-refresh` | Similar: axe allows very long delays (over 20 hours). |
| Empty heading | `empty-heading` | Same, reported as best practice (not in the score) because axe classes it that way. |
| Empty button | `button-name` | Same |
| Empty link | `link-name` | Same |
| Broken skip link | `corea11y-skip-link-broken` | Same for missing targets (added 1.0.0). Keyboard reachability of the skip link is covered by the manual checklist. |
| Empty table header | `empty-table-header` | Same, best practice |
| Blinking content | `blink` | Same |
| Marquee | `marquee` | Same |

## Contrast

| Item | CoreA11y rule | Status |
|---|---|---|
| Very low contrast | `color-contrast`, `corea11y-icon-contrast` | Same where colours can be measured. When the main engine can't decide, CoreA11y measures the text itself if the colour behind it is a plain colour with nothing layered in between (since 1.0.0), and checks icon fonts at 3:1. Text over images, gradients or overlapping elements is listed under "Needs a human check", because the background colour can't be measured reliably. Some tools report a subset of those as errors, so counts can differ. Colours are measured after animations finish (since 1.0.0). |

## Alerts

| Item | CoreA11y rule | Status |
|---|---|---|
| Suspicious alternative text | `ibm-img-alt-misuse` (Extended checks) | Review |
| Redundant alternative text | `image-redundant-alt` | Same, best practice |
| Nearby image has the same alternative text | `corea11y-alt-duplicate` | Same (added 1.0.0) |
| Long alternative text | `corea11y-alt-long` (over 150 characters) | Review (added 1.0.0) |
| Long description present | none | Not checked: informational only. |
| Image with title | `corea11y-image-title-only` | Same (added 1.0.0) |
| Orphaned form label | `corea11y-label-orphaned` | Same (added 1.0.0) |
| Unlabeled form control with title | `label-title-only` | Same, best practice (added 1.0.0) |
| Select missing label | `select-name` | Same |
| Missing fieldset | `ibm-input-checkboxes-grouped` (Extended checks) | Same |
| Fieldset missing legend | `ibm-fieldset-legend-valid` (Extended checks) | Same |
| No heading structure / Missing first level heading | `page-has-heading-one` | Same, best practice. Pro fix: "Add a main heading where a page has none". |
| No page regions | `corea11y-no-landmarks`, `bypass` | Same (added 1.0.0) |
| Skipped heading level | `heading-order` | Same, best practice |
| Possible heading | `ibm-text-block-heading` (Extended checks) | Review |
| Layout table | `corea11y-table-no-headers` | Similar: tables without header cells are listed for review. |
| Possible table caption | none | Not checked: low reliability. |
| Possible list | `ibm-list-markup-review` (Extended checks) | Review |
| Broken same-page link | `corea11y-link-anchor-missing` | Same (added 1.0.0) |
| Suspicious link text | `corea11y-link-generic`, `corea11y-link-symbol-only` | Same |
| Redundant link | `corea11y-link-adjacent-duplicate` | Same |
| Links to Word, Excel, PowerPoint, PDF, other documents | `corea11y-link-file-type` | Similar: only flagged when the link text doesn't say the file type (that's the actual barrier). |
| Audio/Video, HTML5 video or audio | `video-caption`, `corea11y-media-link`, `ibm-media-*` | Review |
| YouTube video (and other embedded video) | `corea11y-video-captions` | Review (added 1.0.0) |
| Flash, Java applet, plugin | `object-alt` | Similar: obsolete technologies, unsupported by current browsers. |
| Noscript element | none | Not checked: not a barrier by itself. |
| Device dependent event handler, JavaScript jump menu | none | Not checked automatically: covered by the keyboard steps of the manual checklist. |
| Accesskey | `corea11y-accesskey` | Same. Lite fix removes them. |
| Tabindex | `tabindex` | Same. Lite fix restores natural order. |
| Very small text | `corea11y-small-text` (under 12px) | Same |
| Justified text | `corea11y-justified-text` | Same |
| Underlined text | `corea11y-underline-text` | Same (added 1.0.0) |
| Redundant title text | `corea11y-link-title-repeat`, `corea11y-image-title-repeat` | Same (images added 1.0.0) |

## Features, structure and ARIA items

These are informational (they show what is present, such as headings, landmarks and ARIA attributes), not problems, so they aren't reported. ARIA misuse is covered by axe's ARIA rules.

## Wording rules for the product

- Never say a site is accessible or compliant. Say what the automated checks found.
- "Fixed and verified" only covers the rules each fix targets, on the pages re-checked.
- "Has a one-click fix" (not "can be fixed"): a fix may solve only part of a problem, which the verification then shows.
- Counts can differ from other tools; the user guide explains why.
