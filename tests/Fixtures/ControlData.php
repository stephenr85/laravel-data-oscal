<?php

namespace Rushing\DataOscal\Tests\Fixtures;

use Spatie\LaravelData\Data;

/** A minimal host Data class for exercising the registry — names no OSCAL/compliance words itself. */
class ControlData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
    ) {}
}
