<?php

namespace Rushing\DataOscal\Tests\Fixtures;

use Rushing\DataOscal\Contracts\OscalProjector;
use Spatie\LaravelData\Data;

/** A fixture projector: maps a {@see ControlData} to a minimal OSCAL implemented-requirement node. */
class ControlProjector implements OscalProjector
{
    public function subject(): string
    {
        return ControlData::class;
    }

    public function project(Data $data): array
    {
        assert($data instanceof ControlData);

        return [
            'control-id' => strtolower($data->id),
            'description' => $data->title,
        ];
    }
}
