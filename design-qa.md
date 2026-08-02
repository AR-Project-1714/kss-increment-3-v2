# Design QA — Dashboard Manajer

## Evidence

- Source visual truth: `C:\Users\Muhammad Arobi\AppData\Local\Temp\codex-clipboard-48c66e8b-c5db-4c85-8b65-a45d2c180b78.png`
- Rendered implementation: `C:\laragon\www\app-kss-codex\design-qa-dashboard-desktop.png`
- Focused implementation card: `C:\laragon\www\app-kss-codex\design-qa-dashboard-card.png`
- Combined source/implementation comparison: `C:\laragon\www\app-kss-codex\design-qa-dashboard-comparison.png`
- Responsive evidence: `C:\laragon\www\app-kss-codex\design-qa-dashboard-mobile.png`
- Route/state: `http://127.0.0.1:8000/manajer`, authenticated manager, light theme, populated data.
- Source image: 1440 × 456 px; focused source card: 329 × 166 px.
- Desktop viewport and screenshot: 1440 × 900 CSS px at density 1; focused implementation card: 275 × 110 px.
- Mobile viewport and screenshot: 390 × 844 CSS px at density 1.
- Density normalization: no raster resampling. The component-size difference is intentional because the reference is a standalone design frame while the implementation lives in the existing four-column dashboard; the user's explicit CSS dimensions are treated as authoritative.

## Full-view comparison evidence

The rendered dashboard preserves the selected lower-frame composition: four cards per desktop row, identity and arrow on top, value/unit and comparison badge in the middle, then the vessel or Rit/DO count below. The seven-card grid forms two compact rows and does not horizontally overflow.

## Focused region comparison evidence

The combined comparison places the first card from the selected reference beside the rendered “Muat Kantong” card. Both use the same three-level hierarchy and alignment. The implementation intentionally retains the product's existing Poppins/Flaticon assets and color tokens. Exact requested computed values are present: primary value 18 px, unit 9 px, and icon box 28 × 28 px.

## Required fidelity surfaces

- Fonts and typography: existing Poppins family retained; primary values compute to 18 px/600, units to 9 px, labels remain legible, and numeric alignment uses tabular figures.
- Spacing and layout rhythm: four equal desktop tracks, 12 px gaps, 110 px card height, 12 × 14 px padding, and a stable single-column mobile reflow.
- Colors and visual tokens: existing activity tints, semantic comparison badge colors, white surfaces, borders, and foreground tokens are preserved.
- Image and asset fidelity: the incumbent Flaticon icon set is used; no emoji, placeholder, custom SVG, CSS drawing, or degraded raster asset was introduced.
- Copy and content: every populated badge renders the dynamic previous calendar month as `vs juli`; YTD totals and supporting counts remain unchanged.

## Findings

- No actionable P0, P1, or P2 mismatch remains.
- Accepted constraint: cards are smaller than the standalone reference frame because the user previously requested a compact four-column dashboard and now supplied exact 18 px/9 px/28 px measurements.

## Interaction and accessibility checks

- Seven cards and seven uniquely named detail links render.
- The “Muat Kantong” arrow navigates to `/manajer/performa?dari=2026-01-01&sampai=2026-08-01`.
- No console errors were recorded.
- Desktop and 390 px mobile layouts have no horizontal overflow.

## Comparison history

- First post-build comparison: no P0/P1/P2 mismatch found; no corrective visual iteration was required.

## Implementation checklist

- [x] Four cards per desktop row.
- [x] Primary values at 18 px.
- [x] Ton/MT/Teus units at 9 px.
- [x] Icon boxes at 28 × 28 px.
- [x] Dynamic `vs juli` comparison copy.
- [x] Desktop/mobile rendering and detail-route verification.

## Follow-up polish

- No required P3 follow-up.

final result: passed
