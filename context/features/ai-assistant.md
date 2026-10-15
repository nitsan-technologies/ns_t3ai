# Feature — AI assistant (Page module panel + CKEditor)

*Status: Done (free version). Code-read (2026-10-15); the Page module panel was seen on v14.3.7, the OpenAI
calls and the CKEditor dialog were not tested (no API key).*

## Setup

1. Extension settings (`ext_conf_template.txt`): `apiKey` (OpenAI), `model` (`gpt-4o` default or `gpt-4`),
   `openAiPromptPrefixPageTitle` (default title prompt; `[Content]` is replaced by the page text).
2. The frontend of the page must be reachable from the server: the prompt text comes from an HTTP request to
   the page with `type=1696828748` (`pageContent` PAGE, colPos 0–2).
3. Optional: custom language records (`tx_nst3ai_domain_model_customlanguage`: ISO code → language name) extend
   the built-in map used to say "in <language>" in prompts; the language comes from the site language locale.
4. The RTE preset is active everywhere (`default` is replaced, see `core.md`); the page TSconfig file
   "NS T3Ai :: Config RTE Preset" sets `RTE.default.preset = nst3ai` explicitly.

## Page module panel (AI SEO)

- `ModifyPageLayoutContentEvent` → `PageLayoutHeaderV12` renders `T3Ai.html` above the page content: top bar
  "T3AI" + "Upgrade Premium?" link, button "Optimize AI-SEO", tab "Metadata" with a prompt select
  (Professional / Optimized / Catchy from `NsExtensionConfiguration`) and a generate button.
- Generate → AJAX `seo_title_generation` (`fieldName` + `_generation`) → `NsT3AiContentService` fetches the page,
  strips scripts/styles/nav/footer/tags, sends the prompt to OpenAI (`/v1/chat/completions` for chat models) and
  returns `GenerateSuggestions.html` (a list of suggestions).
- "Save Changes" → AJAX `save_request` → `PageRepository::saveField()` writes the chosen text into the `pages` field
  (`seo_title`) of the page or its translation for the selected language, directly via the connection (no
  DataHandler, no history).

## CKEditor button

- `pluginv12.js` registers the `t3ai` toolbar item; it opens a modal with the `rte_template` route (`Rte.html`,
  tabs General / Advanced: prompt, temperature, result amount).
- Generate → AJAX `rte_content_generation` with prompt, `gpt-4o`, `max_tokens` 4000 → OpenAI; the escaped text
  is returned as `<p>` blocks. Submit calls `editor.setData()`, which **replaces** the whole editor content
  with the dialog text (not an insert at the cursor).
