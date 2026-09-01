<?php

use Rushing\DataOscal\Contracts\OscalProjector;
use Rushing\DataOscal\OscalProjectionRegistry;
use Rushing\DataOscal\Tests\Fixtures\ControlData;
use Rushing\DataOscal\Tests\Fixtures\ControlProjector;
use Rushing\Popcorn\Registries\Registry;
use Rushing\Popcorn\Registries\RegistryIndex;
use Spatie\LaravelData\Data;

// The tripwire (registry-kernel ticket 27 D3). An unshared RegistryIndex is auto-resolvable, so the
// membership assertion below would pass over a throwaway index. This is the assertion that cannot.
it('shares one RegistryIndex across the container', function () {
    expect(app(RegistryIndex::class))->toBe(app(RegistryIndex::class));
});

it('holds oscal.projectors in the index, resolving to the bound singleton', function () {
    $index = app(RegistryIndex::class);

    expect($index->has('oscal.projectors'))->toBeTrue()
        ->and($index->resolve('oscal.projectors'))->toBe(app(OscalProjectionRegistry::class));
});

it('round-trips a projector through the port vocabulary and the contract alike', function () {
    $projector = new ControlProjector;
    $registry = (new OscalProjectionRegistry)->register($projector);

    expect($registry)->toBeInstanceOf(Registry::class)
        ->and($registry->for(ControlData::class))->toBe($projector)
        ->and($registry->resolve(ControlData::class))->toBe($projector)
        ->and($registry->tryResolve(ControlData::class))->toBe($projector)
        ->and($registry->subjects())->toBe(['rushing.data-oscal.tests.fixtures.control-data'])
        ->and((string) $registry->keys()[0])
        ->toBe('oscal.projectors.rushing.data-oscal.tests.fixtures.control-data');
});

// Spine step 6: the throwing accessor keeps its own exception, and its nullable twin is published
// rather than left to the caller to synthesise by catching a kernel type it never imported.
it('pairs a throwing for() with a nullable tryFor()', function () {
    $registry = new OscalProjectionRegistry;

    expect($registry->tryFor(ControlData::class))->toBeNull()
        ->and($registry->tryResolve(ControlData::class))->toBeNull()
        ->and($registry->has(ControlData::class))->toBeFalse();
});

// Registry-kernel ticket 58: a read fed by a request or stored data must answer "no", not raise
// InvalidRegistryKey before a miss is ever considered.
it('answers no for a key that is not even legal, rather than throwing at parse time', function () {
    $registry = new OscalProjectionRegistry;

    expect($registry->has('Not A Legal Key'))->toBeFalse()
        ->and($registry->tryResolve('Not A Legal Key'))->toBeNull()
        ->and($registry->matches('Not A Legal Key'))->toBe([]);
});

// The whole class path is the key, not `Key::fromClass()`'s basename. Two same-named DTOs in two
// namespaces are two entries, where a basename derivation would have superseded one with the other.
it('keeps two same-named subject classes apart', function () {
    $one = new class implements OscalProjector
    {
        public function subject(): string
        {
            return 'App\\Alpha\\AssessmentData';
        }

        public function project(Data $data): array
        {
            return ['from' => 'alpha'];
        }
    };

    $two = new class implements OscalProjector
    {
        public function subject(): string
        {
            return 'App\\Beta\\AssessmentData';
        }

        public function project(Data $data): array
        {
            return ['from' => 'beta'];
        }
    };

    $registry = (new OscalProjectionRegistry)->register($one)->register($two);

    expect($registry->has('App\\Alpha\\AssessmentData'))->toBeTrue()
        ->and($registry->has('App\\Beta\\AssessmentData'))->toBeTrue()
        ->and($registry->for('App\\Alpha\\AssessmentData'))->toBe($one)
        ->and($registry->for('App\\Beta\\AssessmentData'))->toBe($two)
        ->and($registry->keys())->toHaveCount(2);
});
