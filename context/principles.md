# ns_t3ai — Operating Principles

*Every change to code, context or docs must serve the goals below.*

---

## Goal 1 — Nothing that works may break

The extension declares TYPO3 v13–v14 (Composer and Classic mode), see `core.md`.

- Keep both declared majors working. Follow the existing version switches (`version_main` checks in
  `PageLayoutHeaderV12`, `ext_localconf.php`, `ext_tables.php` and `Services.php`; `enum_exists(IconSize::class)`
  in `PremiumStrip`) instead of inventing new ones.
- The code still carries TYPO3 ≤ v11 branches (`PageLayoutHeader`, `Module.js`, `Plugin.yaml`, `v10` template).
  They are unreachable on v13/v14; remove them only as a deliberate clean-up, not as a side effect.
- DI is split: `Configuration/Services.yaml` tags the Page module event listener, `Configuration/Services.php`
  loads all classes and wires the factories (`ExtConf.nsT3ai`, `CustomLanguageArray`, `SelectedModel`). Wire
  new services in `Services.php` unless they only need a tag.
- Never rename AJAX route keys (`seo_title_generation`, `save_request`, `rte_content_generation`,
  `rte_template`) — the JavaScript builds route names from field names (`<fieldName>_generation`).
- Prefer the smallest change that fixes the root cause. No drive-by refactors.
- Run `tasks/run-quality.md` and re-test the affected flow before claiming done.

## Goal 2 — Token-cost efficiency (agent context)

- **DRY context:** each fact lives in exactly ONE file. Reference, don't copy.
- **Load on demand:** read `context/features/<area>.md` for the task, not everything.
- **Prune as you go:** keep `context/session-state.md` short.

## Goal 3 — Accuracy and trust

- Answer from context + code, never from invented API, table or field names.
- Mark statements as verified live or code-read; conflicting facts across files = fix the source file.

---

## Guardrails (non-negotiable)

- Never commit an OpenAI API key; it belongs in the extension configuration of an install.
- Page content and prompts are sent to OpenAI: don't widen what is sent (other fields, other pages, user data)
  without saying so in the feature context.
- Installing the premium version through ns_license replaces this extension's folder: never keep user data in
  files below the extension.
- Do not change ns_license from an ns_t3ai task; reuse its module and trial modal.
- All UI text goes into the English `Resources/Private/Language/*.xlf` files, no hard-coded labels.
  Translations are not part of this repo; don't add them here.

---

## Apply this whenever you

- Edit `context/` or `AGENTS.md`
- Land a feature or bugfix (update the matching `context/features/*.md` + `session-state.md`)
