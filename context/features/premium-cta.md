# Feature — Premium strip and ns_license free trial

*Status: Done. Verified live on TYPO3 14.3.7 (2026-10-15): strip once, directly above the T3AI panel in the
Page module, same width as the panel; the button opens the ns_license module with "AI Assistant (ns_t3ai)"
preselected in Free Trial mode. Requires ns_license 14.5.4 (deep-link support) to be released. v13 not tested.*

## Licensing status

Freemium. The T3AI panel's top bar keeps its own "Upgrade Premium?" link to
https://t3planet.de/en/t3ai-typo3-extension; the strip adds the in-backend trial path next to it.

## Dependency

`composer.json` `"nitsan/ns-license": "^14.5.4"`, `ext_emconf.php` `'ns_license' => '14.5.4-14.9.99'`. Does not
raise the minimum TYPO3 version (see `core.md`).

## Placement

| Place | Position |
|---|---|
| Page module (`web_layout`), header content | Directly above the T3AI panel (`section.ns-t3ai`), 16px gap |

Shown only when the panel is shown (existing page, not hidden, a single language selected). Not in the
CKEditor T3AI dialog: it is an iframe, so the same-frame trial link would open inside the dialog.

## How it works

- `Backend\PremiumStrip::render()`: `actions-rocket` icon, headline and description on two lines,
  `btn btn-primary` "Start Free Trial →" (the arrow is `aria-hidden` markup). Link = route
  `nitsan_nslicensemodule` + `trial=ns_t3ai` + `#free-section`, same frame. Returns `''` without ns_license.
- `Backend\Listener\ModifyPageLayoutContent` renders the panel first; if the panel and the strip are both
  non-empty it adds `PremiumStrip::STYLESHEET` through `PageRenderer` and prepends the strip to the header
  content. `PremiumStrip` is autowired by the `Classes/` load in `Services.php`.

## Strip

- Labels in `locallang.xlf`: `premium.strip.headline` "You're using the free version of NS T3AI",
  `premium.strip.description` "Unlock premium features and get more out of NS T3AI.",
  `premium.strip.trial` "Start Free Trial".
- `Resources/Public/Css/backend-premium-strip.css`: `.ns-t3ai-premium-strip` — team-wide design (light-blue card
  `#e7f2fc`, border `#8eb6e8`, 4px `#2b6cb0` left accent, 6px radius, padding 14px 18px, 1rem bottom margin,
  `light-dark()` for dark mode, button `#007bff` / hover `#0062cc`).
- Measured on v14.3.7: 676×67px with a 676px panel (582×67px at a narrower width).
- ns_license's module requires system maintainer access; other users get access denied after clicking.
