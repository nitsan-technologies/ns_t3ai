# ns_t3ai — Architecture

Backend-only extension: a T3AI panel in the Page module (event listener), a CKEditor button, four backend
AJAX routes and one small record table. No content element, no backend module, no frontend plugin.

## Configuration

| File | What |
|---|---|
| `ext_localconf.php` | RTE presets `default` and `nst3ai` → `Configuration/RTE/Pluginv12.yaml` (v12+; `Plugin.yaml` and a draw-header hook for ≤ v11); `addTypoScript` setup import of `t3ai.typoscript` |
| `ext_tables.php` | v12+: adds `locallang_be.xlf` inline labels and `Rte.css` to the backend page renderer |
| `ext_conf_template.txt` | `apiKey`, `model` (`gpt-4` / `gpt-4o`), `openAiPromptPrefixPageTitle` |
| `ext_tables.sql`, `Configuration/TCA/tx_nst3ai_domain_model_customlanguage.php` | Custom language table (`iso_code`, `speech`) |
| `Configuration/TCA/Overrides/pages.php` | Selectable page TSconfig "NS T3Ai :: Config RTE Preset" (`RTE.default.preset = nst3ai`) |
| `Configuration/TypoScript/t3ai.typoscript` | `pageContent` PAGE, typeNum 1696828748: content of colPos 0–2 without header code (source for AI prompts) |
| `Configuration/Backend/AjaxRoutes.php` | `seo_title_generation`, `save_request`, `rte_content_generation`, `rte_template` → `T3AiController` |
| `Configuration/JavaScriptModules.php` | `@nitsan/nst3ai/ModuleV12.js`, `@nitsan/nst3ai/pluginv12.js` (+ a missing `t3ai-plugin.js`) |
| `Configuration/RTE/Pluginv12.yaml` | Imports core `Processing`/`Base`/`Plugins`/`Default` presets, adds module `T3Ai` and toolbar item `t3ai` |
| `Configuration/Services.yaml` | Tags `Backend\Listener\ModifyPageLayoutContent` for `ModifyPageLayoutContentEvent` |
| `Configuration/Services.php` | Loads `Classes/` (minus `Domain/Model`); factories `ExtConf.nsT3ai`, `CustomLanguageArray`, `SelectedModel`, logger; explicit args for the service, controller and header |
| `Configuration/ContentSecurityPolicies.php` | Backend `connect-src` for `https://api.openai.com/v1/completions` |
| `Configuration/Icons.php` | `t3ai_module`, `actions-localize-nst3ai` |

## Classes

| Class | Role |
|---|---|
| `Backend\Listener\ModifyPageLayoutContent` | Renders the T3AI panel; when it is not empty, prepends the premium strip and its stylesheet |
| `Backend\PageLayoutHeaderV12` | Panel view (`Backend/Templates/T3Ai.html`); skipped for hidden pages, missing page records and "all languages"; loads `ModuleV12.js`, `Style.css` |
| `Backend\PageLayoutHeader`, `Backend\AbstractPageLayoutHeader` | ≤ v11 header hook (unused on v13/v14) |
| `Backend\PremiumStrip` | Strip HTML + trial URL; `''` when the ns_license route is missing |
| `Controller\T3AiController` | AJAX actions: page title suggestions, save to `pages`, RTE generation, RTE dialog template |
| `Service\NsT3AiContentService` | Builds the `pageContent` preview URL, fetches and strips it, calls OpenAI (chat or legacy completions), renders `GenerateSuggestions.html` / `Rte.html` |
| `Domain\Repository\PageRepository` | Reads page SEO fields; writes the chosen suggestion |
| `Domain\Model\CustomLanguage`, `Domain\Repository\CustomLanguageRepository`, `Factory\CustomLanguageFactory` | ISO code → language name map (built-in + records) for the prompt language |
| `Factory\SelectedModelFactory` | `true` for chat models (`gpt-4`, `gpt-4o`) |
| `Helper\NsExtensionConfiguration` | Prompt presets for the title select (Professional, Optimized, Catchy) |
| `Utility\NsT3AiBackendUtility` | API key lookup (≤ v11 path) |

## Views and assets

- `Resources/Private/Backend/Templates/T3Ai.html` — panel ("Optimize AI-SEO" button, "Metadata" tab);
  `v10/T3Ai.html` legacy.
- `Resources/Private/Templates/T3Ai/{GenerateSuggestions,Rte}.html`, `Layouts/Default.html`.
- JS: `ModuleV12.js` (panel), `Plugins/nst3ai/pluginv12.js` (CKEditor plugin); `Module.js`, `plugin.js` legacy.
- CSS: `Style.css` (panel), `Rte.css` (RTE dialog), `backend-premium-strip.css`, `Vendor/backend.css`.
