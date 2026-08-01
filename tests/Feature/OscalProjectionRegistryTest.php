<?php

use Rushing\DataOscal\Exceptions\UnknownProjector;
use Rushing\DataOscal\OscalProjectionRegistry;
use Rushing\DataOscal\Tests\Fixtures\ControlData;
use Rushing\DataOscal\Tests\Fixtures\ControlProjector;

it('dispatches by Data class and projects', function () {
    $registry = (new OscalProjectionRegistry)->register(new ControlProjector);

    expect($registry->has(ControlData::class))->toBeTrue()
        ->and($registry->project(new ControlData('CC8.1', 'Change management')))
        ->toBe(['control-id' => 'cc8.1', 'description' => 'Change management']);
});

it('throws UnknownProjector for an unregistered Data class', function () {
    (new OscalProjectionRegistry)->for(ControlData::class);
})->throws(UnknownProjector::class);

it('is registered as a container singleton by the service provider', function () {
    expect(app(OscalProjectionRegistry::class))->toBe(app(OscalProjectionRegistry::class));
});
