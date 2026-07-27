<?php

declare(strict_types=1);

namespace Rushing\DataOscal;

use Rushing\DataOscal\Contracts\OscalProjector;
use Rushing\DataOscal\Exceptions\UnknownProjector;
use Spatie\LaravelData\Data;

/**
 * The dispatch-by-Data-class registry (the mirror of schema-org's `SchemaOrgRegistry`). One Data
 * class = exactly one {@see OscalProjector}; dispatch is strict on `$data::class`, never on node
 * shape, so routing is deterministic and an unknown class throws loudly.
 */
final class OscalProjectionRegistry
{
    /** @var array<class-string<Data>, OscalProjector> */
    private array $byClass = [];

    public function register(OscalProjector $projector): self
    {
        $this->byClass[$projector->subject()] = $projector;

        return $this;
    }

    public function for(string $dataClass): OscalProjector
    {
        return $this->byClass[$dataClass] ?? throw new UnknownProjector($dataClass);
    }

    public function has(string $dataClass): bool
    {
        return isset($this->byClass[$dataClass]);
    }

    /** @return array<string, mixed> */
    public function project(Data $data): array
    {
        return $this->for($data::class)->project($data);
    }
}
