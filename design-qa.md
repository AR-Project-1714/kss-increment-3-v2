# Design QA — Dropdown Profil Petugas

## Evidence

- Source visual truth: `C:\Users\MUHAMM~1\AppData\Local\Temp\codex-clipboard-de47bbad-10dd-4396-8e3c-c8ba3b0e4e84.png`
- Rendered implementation: `C:\laragon\www\app-kss-codex\design-qa-account-dropdown-desktop-v2.png`
- Compact viewport evidence: `C:\laragon\www\app-kss-codex\design-qa-account-dropdown-desktop.png`
- Intended production routes: `/report-ops`, `/pemeliharaan`, and `/safety`.
- Visual QA route: an exact component harness using the production Bootstrap, Poppins, Flaticon, and compiled petugas CSS. The authenticated production routes redirected the QA browser to login, so production inclusion was verified separately with feature tests.
- Source image: 475 × 652 px at approximately 1.25 density.
- Desktop evidence: 1280 × 720 px; rendered dropdown: 312 × 280.3 CSS px.
- Compact evidence: 478 × 656 CSS px; no horizontal overflow.

## Full-view comparison evidence

The source and implementation were inspected together in one visual comparison input. The redesign carries over the reference's compact account-panel structure: profile first, grouped account action, in-row dark-mode switch, divider, and logout last. Extra reference actions such as switch account, manage account, and add account were intentionally omitted per the product requirements.

## Focused region comparison evidence

The profile block is the dominant row, with a 44 px avatar, three levels of identity information, and a camera affordance that preserves the existing photo-management flow. The actions use the product's Poppins typography, Flaticon icons, blue focus/hover language, and red destructive treatment. The implementation is intentionally denser than the reference so it remains consistent with the existing KSS header and component scale.

## Required fidelity surfaces

- Typography: Poppins and the existing KSS text weights/sizes are retained.
- Spacing: 10 px panel inset, 46 px action rows, 44 px profile avatar, and consistent 7 px group dividers.
- Color and theme: existing KSS tokens drive surfaces, borders, foregrounds, hover states, danger states, and dark mode.
- Assets: the incumbent Flaticon icon set is used; no emoji, placeholder icon, custom SVG, or CSS-drawn icon was introduced.
- Copy: only Profil Pengguna, Ubah Password, Mode Gelap, and Keluar are exposed.

## Findings

- No actionable P0, P1, or P2 visual mismatch remains.
- Accepted deviation: logout remains semantically red instead of neutral like the reference because the existing KSS design system treats destructive account actions as danger actions.
- Accepted constraint: visual capture uses the exact production component harness because authenticated pages were unavailable to the QA browser. Blade inclusion and rendered production markup are covered by automated feature tests.

## Interaction and accessibility checks

- Profile card opens the existing photo-management modal.
- Ubah Password opens the existing password modal.
- The theme control is a real checkbox with `role="switch"`, dynamic label/status, localStorage persistence, and light/dark synchronization.
- Dark-state inspection confirmed the checked switch, `Aktif` status, dark KSS surfaces, and no horizontal overflow.
- Logout remains a POST form with CSRF protection.
- Trigger/popover ARIA state, visible keyboard focus styles, Escape close behavior, and outside-click close behavior remain present.
- Browser console contained no errors.
- Feature test result: 9 passed, 49 assertions.

## Comparison history

- First capture exposed harness-only sizing drift because Bootstrap's global `box-sizing` reset was missing.
- The harness was corrected to load the same Bootstrap layer as production; the panel then measured 312 × 280.3 CSS px and all action rows aligned at the intended rhythm.
- Final source/implementation comparison found no P0/P1/P2 issue requiring another code iteration.

## Implementation checklist

- [x] Profile identity is the first and most prominent block.
- [x] Change-password action is retained.
- [x] Dark mode moved into the dropdown as a working switch.
- [x] Logout is the final, separated destructive action.
- [x] External petugas theme/logout controls were removed from the header.
- [x] Light, dark, compact-width, keyboard-focus, and overflow states were checked.
- [x] Production build and feature tests passed.

## Follow-up polish

- No required P3 follow-up.

final result: passed
