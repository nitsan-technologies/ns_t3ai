# ns_t3ai — Core facts

## Identity

| Item | Value |
|---|---|
| Extension key | `ns_t3ai` |
| Composer name | `nitsan/ns-t3ai` |
| Title (ext_emconf) | T3AI – TYPO3 AI Integration Extension (category `be`) |
| Namespace | `NITSAN\NsT3Ai\` → `Classes/` |
| Version (ext_emconf) | 14.1.0 (latest tag 14.0.0) |
| Repository | https://github.com/nitsan-technologies/ns_t3ai (default branch `master`) |
| Licensing status | **Freemium** — this repo is the free version; the premium version is sold by T3Planet and listed in ns_license's trial modal as "AI Assistant (ns_t3ai)" |

## Compatibility

| Source | TYPO3 | PHP |
|---|---|---|
| `ext_emconf.php` | `13.0.0-14.99.9` | not declared |
| `composer.json` | `^13 \|\| ^14` | not declared (TYPO3 13 needs 8.2+) |
| ns_license 14.5.4 (dependency) | `12.4.18-14.9.99` | not declared |

- ns_license does not raise the minimum: TYPO3 13.0, PHP 8.2.

## Dependencies

| Package | Constraint | Why |
|---|---|---|
| `typo3/cms-core` | see above | core |
| `nitsan/ns-license` / `ns_license` | `^14.5.4` / `14.5.4-14.9.99` | TYPO3 Shop module + free-trial deep link (see `features/premium-cta.md`) |

Undeclared but used: `rte_ckeditor` (RTE preset), `extbase`/`fluid` (views, repository). External: OpenAI API
(`api.openai.com`, needs an API key).

## Tooling

- No PHPUnit, PHPStan, CS fixer or package.json in the repo. `composer.json` has a `.Build/` vendor/web-dir
  config for standalone installs.
- CI: `.github/workflows/ter-release.yml` only (publishes tags to TER with tailor; PHP 7.4).
- `.gitattributes` export-ignores `.github/`, `AGENTS.md`, `CLAUDE.md`, `context/`, `tasks/` and itself, so they
  are not in the GitHub/Composer archive or the TER package (the TER release uploads the GitHub tag archive).
- No Crowdin config. Language files: `locallang.xlf` (Page module panel + premium strip), `locallang_be.xlf`
  (extension settings, JS labels, RTE dialog, custom language table), `locallang_t3ai.xlf`.

## Conventions

- PHP: 4 spaces, mixed brace and `strict_types` style; `PremiumStrip` follows PSR-12 with `strict_types`.
- Fluid, YAML, XLF (`locallang.xlf`): 4 spaces; `locallang_be.xlf`: tabs.
- Public assets in `Resources/Public/{Css,Icons,JavaScript,Vendor}/`; strip CSS
  `Resources/Public/Css/backend-premium-strip.css`.

## Known issues (existing, not fixed)

All code-read.

- `T3AiController::logGuzzleError()` is typed `GuzzleException` but is also called with
  `UnableToLinkToPageException` and `TYPO3\CMS\Core\Exception`, which would raise a `TypeError` instead of a
  JSON error.
- `NsT3AiContentService::fetchContentFromUrl()` translates a label from `Resources/Private/Language/backend.xlf`,
  which does not exist.
- `Configuration/JavaScriptModules.php` maps `@nitsan/nst3ai/t3ai-plugin.js` to `JavaScript/Ckeditor/`, which
  does not exist.
- `PageRepository::markPageAsTranslatedWithNsT3Ai()` writes `pages` columns that `ext_tables.sql` does not
  define; it is not called.
- `ext_localconf.php` replaces the global RTE preset `default` with the T3AI preset, so every RTE field uses it;
  it also adds the `pageContent` PAGE object (typeNum 1696828748) to all sites' TypoScript.
- `ext_tables.php` adds `Rte.css` and the `locallang_be.xlf` labels to every backend page.
- The extension setting `model` only offers `gpt-4` / `gpt-4o`; the RTE dialog always sends `gpt-4o` from the
  JavaScript, independent of the setting.
- README badges show v2.0.3 / TYPO3 13 and GPL-3.0 (composer: GPL-2.0-or-later); links use the old
  `t3planet.com` host. `ext_emconf.php` upper bound `14.99.9` is unusual.
