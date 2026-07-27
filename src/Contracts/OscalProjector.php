<?php

declare(strict_types=1);

namespace Rushing\DataOscal\Contracts;

use Spatie\LaravelData\Data;

/**
 * Projects one host {@see Data} class into an OSCAL document node. Registered by the Data class it
 * handles (dispatch-by-Data-class, never by node shape), the exact mirror of the schema-org leaf's
 * `SchemaOrgProjector`. The leaf names no concrete compliance vocabulary — the OSCAL-named structure
 * a projector emits is supplied by the host mapping (e.g. an app's SOC-2 seam), keeping this package
 * the reusable "wheel" and the vertical content the "wagon".
 */
interface OscalProjector
{
    /**
     * The fully-qualified Data class this projector handles.
     *
     * @return class-string<Data>
     */
    public function subject(): string;

    /**
     * Project the given Data instance into an OSCAL document node (a nested array — OSCAL is JSON).
     *
     * @return array<string, mixed>
     */
    public function project(Data $data): array;
}
