<?php

namespace Rushing\DataOscal\Exceptions;

use InvalidArgumentException;

/** Thrown when the registry is asked to project a Data class it has no projector for. */
class UnknownProjector extends InvalidArgumentException
{
    public function __construct(string $dataClass)
    {
        parent::__construct("No OSCAL projector registered for [{$dataClass}].");
    }
}
