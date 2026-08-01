<?php

namespace Rushing\DataOscal;

/**
 * The dereferencer-style reader — the import half of the boundary projection (the mirror of
 * schema-org's reader). Walks an OSCAL document and pulls its findings / implemented-requirements
 * back into neutral maps keyed by `control-id`, the inverse of an {@see Contracts\OscalProjector}.
 * Domain-agnostic: it reads OSCAL structure, never compliance meaning.
 */
class OscalDereferencer
{
    /**
     * @param  array<string, mixed>  $assessmentResults
     * @return array<string, array<string, mixed>>
     */
    public function findings(array $assessmentResults): array
    {
        $out = [];
        foreach ($assessmentResults['assessment-results']['results'] ?? [] as $result) {
            foreach ($result['findings'] ?? [] as $finding) {
                $controlId = $finding['target']['target-id'] ?? null;
                if (is_string($controlId)) {
                    $out[$controlId] = $finding;
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $componentDefinition
     * @return array<string, array<string, mixed>>
     */
    public function implementedRequirements(array $componentDefinition): array
    {
        $out = [];
        foreach ($componentDefinition['component-definition']['components'] ?? [] as $component) {
            foreach ($component['control-implementations'] ?? [] as $ci) {
                foreach ($ci['implemented-requirements'] ?? [] as $ir) {
                    $controlId = $ir['control-id'] ?? null;
                    if (is_string($controlId)) {
                        $out[$controlId] = $ir;
                    }
                }
            }
        }

        return $out;
    }
}
