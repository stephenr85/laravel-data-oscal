# laravel-data-oscal

A **neutral open foundation leaf** (`rushing/*` — a vendor-neutral foundation, not the Splicewire Beam
free-tier family; see ADR-0092 / ADR-0131) that projects `spatie/laravel-data` DTOs to/from **NIST OSCAL**
documents — the structural mirror of `schemastud/laravel-data-schema-org`. It is the reusable **wheel**: a
dispatch-by-Data-class projection registry (`OscalProjectionRegistry`), an `OscalProjector` contract, and a
dereferencer-style reader (`OscalDereferencer`). It names **no** concrete compliance vocabulary — the
OSCAL-named document shapes a projector emits are supplied by the consuming host (the "wagon"), so the
criterion/control/finding mapping lives at the host seam, never here.

This is a **boundary projection, not a rename** (apocryphon `oscal-is-a-boundary-projection-not-a-rename`,
extending ADR-0050): OSCAL's terms *invert* a typical compliance model (OSCAL `control` = a host's
*criterion*; a host's *control* = OSCAL `implemented-requirement`), so the two vocabularies meet only at
this seam. "Maps perfectly" is a **round-trip conformance test** (project → parse → project = structural
equality), not name-parity.

## Provenance

Extracted from the SOC 2 self-audit dogfood's OSCAL projection (`splicewire-app`
`app/Compliance/Soc2/Oscal/*`), per the soc2-ontology-drafting build ticket 08 seam call: *open the wheel,
sell the wagon, keep the vertical app-local.* The host wires its per-Data-class `OscalProjector`(s) into the
registry and consumes the `OscalDereferencer`; the SOC-2 document shapes stay host-side.

## Import

OSCAL **import** is deferred for the SOC 2 dogfood (no licensed TSC-in-OSCAL catalog exists; encoding TSC in
OSCAL launders no copyright). The reader (`OscalDereferencer`) exists and is round-trip-exercised by the
consuming host; the dogfood imports nothing.
