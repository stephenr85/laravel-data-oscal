<?php

declare(strict_types=1);

use Rushing\DataOscal\OscalDereferencer;

it('walks assessment-results findings keyed by control-id', function () {
    $doc = [
        'assessment-results' => [
            'results' => [[
                'findings' => [
                    ['target' => ['target-id' => 'cc8.1', 'status' => ['state' => 'satisfied']]],
                    ['target' => ['target-id' => 'cc6.1', 'status' => ['state' => 'not-satisfied']]],
                    ['target' => ['status' => ['state' => 'x']]], // no target-id — skipped
                ],
            ]],
        ],
    ];

    $findings = (new OscalDereferencer)->findings($doc);

    expect($findings)->toHaveKeys(['cc8.1', 'cc6.1'])
        ->and($findings)->not->toHaveKey('')
        ->and($findings['cc8.1']['target']['status']['state'])->toBe('satisfied');
});

it('walks component-definition implemented-requirements keyed by control-id', function () {
    $doc = [
        'component-definition' => [
            'components' => [[
                'control-implementations' => [[
                    'implemented-requirements' => [
                        ['control-id' => 'cc8.1', 'description' => 'change mgmt'],
                    ],
                ]],
            ]],
        ],
    ];

    $irs = (new OscalDereferencer)->implementedRequirements($doc);

    expect($irs)->toHaveKey('cc8.1')
        ->and($irs['cc8.1']['description'])->toBe('change mgmt');
});

it('returns an empty map for a document with no results/components', function () {
    expect((new OscalDereferencer)->findings([]))->toBe([])
        ->and((new OscalDereferencer)->implementedRequirements([]))->toBe([]);
});
