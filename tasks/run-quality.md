# Run quality checks

The repo has no PHPUnit, PHPStan or CS config. Available checks:

```bash
# PHP syntax; also run once with PHP 8.2, the lowest version TYPO3 13 supports
for f in $(find . -name "*.php" -not -path "./.git/*" -not -path "./.Build/*"); do php -l "$f"; done

# composer.json
composer validate --no-check-publish

# XLF well-formedness
for f in Resources/Private/Language/*.xlf; do xmllint --noout "$f"; done
```

## Manual test (per TYPO3 major 13 / 14)

1. Page module on a visible page with one language selected: T3AI panel with the premium strip once above it.
   Hidden page or "all languages": neither panel nor strip.
2. "Start Free Trial" (as system maintainer) opens the ns_license module and its trial modal with
   "AI Assistant (ns_t3ai)" preselected. Cancel; don't start a trial.
3. Deactivate ns_license (test system only): the panel still renders, without the strip.
4. With an API key: "Optimize AI-SEO" → generate → suggestions → "Save Changes" updates the page's SEO title.
5. With an API key: an RTE field shows the T3AI toolbar button; generate and submit text (it replaces the
   field content).
6. Check light and dark backend themes.

On a shared install where the extension is not active, activate it only temporarily and set the
`serverConnectionTime` cookie before the backend login, otherwise the ns_license check may rename files of
unlicensed NITSAN extensions. Remove the custom language table and extension settings afterwards.
