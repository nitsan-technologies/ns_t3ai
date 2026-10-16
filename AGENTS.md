# AGENTS.md — ns_t3ai (T3AI – TYPO3 AI Integration)

Agent router for the T3AI free extension. Load only what your task needs.

> **Principles:** `context/principles.md`
> **Session log:** `context/session-state.md`
> **Doc index:** `context/docs-map.md`

---

## Always load

| File | Why |
|---|---|
| `context/principles.md` | Guardrails, "nothing that works may break" rule |
| `context/core.md` | Identity, TYPO3/PHP/ns_license constraints, tooling, known issues |

---

## Task router

| Task | Load |
|---|---|
| Page module T3AI panel: AI SEO page title suggestions, saving to `pages` | `context/features/ai-assistant.md` |
| CKEditor T3AI button / AI text generation in the RTE, RTE preset | `context/features/ai-assistant.md` |
| OpenAI API key, model, prompts, custom languages | `context/features/ai-assistant.md` |
| Premium strip / free trial / ns_license dependency | `context/features/premium-cta.md` |
| Files, classes, DI wiring, AJAX routes, assets | `context/architecture.md` |
| Lint / validate / manual test | `tasks/run-quality.md` |
| Update agent context after a change | `tasks/context-update.md` |

---

## Feature status

| Feature | Status | Agent entry |
|---|---|---|
| T3AI panel in the Page module with AI page-title suggestions (OpenAI) | Done (code-read; panel seen on v14.3.7, AI calls not tested) | `context/features/ai-assistant.md` |
| T3AI button in CKEditor for AI text generation (OpenAI) | Done (code-read) | `context/features/ai-assistant.md` |
| Premium strip above the T3AI panel + ns_license free-trial deep link | Done (needs ns_license 14.5.4 release) | `context/features/premium-cta.md` |

---

## Quick commands

```bash
cd packages/ns_t3ai          # or typo3conf/ext/ns_t3ai in Classic mode
for f in $(find . -name "*.php" -not -path "./.git/*"); do php -l "$f"; done
composer validate --no-check-publish
for f in Resources/Private/Language/*.xlf; do xmllint --noout "$f"; done
```
