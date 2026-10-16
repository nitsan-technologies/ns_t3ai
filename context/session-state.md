# ns_t3ai — Session state

## 2026-10-15 — ns_license integration + premium strip + agent context

- Branch `feature/ns-license-premium-strip` from `master` (d561fab).
- Added `nitsan/ns-license ^14.5.4` / `ns_license 14.5.4-14.9.99` (minimum stays TYPO3 13.0).
  Version 14.0.0 → 14.1.0.
- Premium strip with "Start Free Trial" → ns_license module `?trial=ns_t3ai#free-section`, prepended to the T3AI
  panel in the Page module, team-wide wording and design (see `features/premium-cta.md`).
- Verified live on TYPO3 14.3.7 classic: extension temporarily added to `PackageStates.php` and set up
  (custom language table, extension settings), `serverConnectionTime` cookie set before login, temporary
  system-maintainer admin. All removed (table dropped, registry rows deleted); `PackageStates.php` and
  `settings.php` restored byte-identical; no other extension's files changed; the extension is **not active**
  in that install. PHP 8.4 and 8.2 lint clean.
- Created agent context (`AGENTS.md`, `CLAUDE.md`, `context/`, `tasks/`) and `.gitattributes`; no previous
  context existed.
- Open: ns_license 14.5.4 not yet published; v13 not tested; AI calls not tested (no API key).
