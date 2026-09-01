<?php

namespace Rushing\DataOscal;

use Rushing\DataOscal\Contracts\OscalProjector;
use Rushing\DataOscal\Exceptions\UnknownProjector;
use Rushing\Popcorn\Registries\Authorizer;
use Rushing\Popcorn\Registries\BasicRegistry;
use Rushing\Popcorn\Registries\Gated;
use Rushing\Popcorn\Registries\IsRegistry;
use Rushing\Popcorn\Registries\Key;
use Rushing\Popcorn\Registries\OnDuplicate;
use Rushing\Popcorn\Registries\Optionality;
use Rushing\Popcorn\Registries\Registry;
use Rushing\Popcorn\Registries\RegistryArity;
use Rushing\Popcorn\Registries\RegistryKey;
use Spatie\LaravelData\Data;

/**
 * The dispatch-by-Data-class registry (the mirror of schema-org's `SchemaOrgRegistry`). One Data
 * class = exactly one {@see OscalProjector}; dispatch is strict on `$data::class`, never on node
 * shape, so routing is deterministic and an unknown class throws loudly.
 *
 * ## The key is the WHOLE class path, not its basename
 *
 * `Key::fromClass()` is the kernel's sanctioned derivation for class-strings, and it takes the
 * **basename** — right where the class-string is the ENTRY and nothing looks up by key. Here the class
 * IS the lookup identity, and two `AssessmentData` classes in two namespaces would have collided on
 * `assessment-data` under {@see OnDuplicate::Supersede}, silently losing a projector at registration.
 * So the namespace becomes the key's segments and the short name keeps `fromClass()`'s kebab rule:
 * `Splicewire\Tower\Determination\Soc2\Oscal\Soc2AssessmentData` →
 * `oscal.projectors.splicewire.tower.determination.soc2.oscal.soc2-assessment-data`. Every historical
 * caller still spells the FQCN and never sees this. This is the answer `SchemaOrgRegistry` reached for
 * the same keyspace, adopted here rather than re-derived.
 *
 * ## Miss behaviour is UNCHANGED
 *
 * {@see for()} still throws {@see UnknownProjector}, not the kernel's `RegistryMiss` — it is the
 * vocabulary the consumers speak, and re-pointing it is the regression registry-kernel ticket 61
 * records. {@see tryFor()} is the nullable twin the kernel pairing owes (spine step 6): a caller
 * holding a class-string it took off a request now has somewhere to go that is neither a throw nor a
 * caught kernel exception it never imported.
 *
 * @implements Registry<OscalProjector>
 */
#[IsRegistry(
    root: 'oscal.projectors',
    of: 'OSCAL projectors keyed by the Data class each handles — the emit-side leaf a host maps its '
        .'own compliance vocabulary onto',
    arity: RegistryArity::PickOne,
    entryType: OscalProjector::class,
    onDuplicate: OnDuplicate::Supersede,
    optionality: Optionality::Optional,
    note: 'PickOne because `project()` LOOKS like a fan-out and is not one — it dispatches ONE instance '
        .'by $data::class to exactly one projector. The projector names its own key via subject(), so '
        .'there is no key to put in config and nothing hand-registers a key. Supersede matches the '
        .'behaviour this class has always had: registration was a plain array assignment, so a second '
        .'projector for one Data class replaced the first and nothing reported it. The key is the '
        .'subject class path segment-mapped, not `Key::fromClass()`\'s basename, because a basename '
        .'derivation would let two same-named DTOs supersede each other.',
    order: 54,
)]
class OscalProjectionRegistry implements Gated, Registry
{
    /** @var BasicRegistry<OscalProjector> */
    private BasicRegistry $entries;

    public function __construct()
    {
        $this->entries = BasicRegistry::for($this);
    }

    /**
     * Register a projector under the Data class it names via {@see OscalProjector::subject()}.
     *
     * WIDENED from the contract rather than shadowing it — contravariance, so the historical
     * one-argument self-keying call `register($projector)` keeps working unchanged.
     */
    public function register(RegistryKey|string|OscalProjector $key, mixed $entry = null, ?string $by = null, ?string $ability = null): static
    {
        if ($key instanceof OscalProjector) {
            $entry = $key;
            $key = $key->subject();
        }

        $this->entries->register($this->keyFor($key), $entry, $by, $ability);

        return $this;
    }

    /**
     * The projector for a Data class, or {@see UnknownProjector}.
     *
     * Port sugar over {@see tryResolve()}, with its ORIGINAL miss behaviour preserved.
     *
     * @param  class-string<Data>  $dataClass
     */
    public function for(string $dataClass): OscalProjector
    {
        return $this->tryFor($dataClass) ?? throw new UnknownProjector($dataClass);
    }

    /**
     * The projector for a Data class, or null — the nullable twin of {@see for()}.
     *
     * @param  class-string<Data>|string  $dataClass
     */
    public function tryFor(string $dataClass): ?OscalProjector
    {
        $entry = $this->tryResolve($dataClass);

        return $entry instanceof OscalProjector ? $entry : null;
    }

    /**
     * The registered subject classes, as keys relative to the declared root — keys go relative in and
     * absolute out (registry-kernel ticket 20 D2).
     *
     * @return string[]
     */
    public function subjects(): array
    {
        return $this->entries->relativeKeys();
    }

    /**
     * Look up the projector for the concrete Data instance and project it.
     *
     * @return array<string, mixed>
     */
    public function project(Data $data): array
    {
        return $this->for($data::class)->project($data);
    }

    /** @param  RegistryKey|class-string<Data>|string  $key */
    public function has(RegistryKey|string $key): bool
    {
        $derived = $this->tryKeyFor($key);

        return $derived !== null && $this->entries->has($derived);
    }

    public function resolve(RegistryKey|string $key): mixed
    {
        return $this->entries->resolve($this->keyFor($key));
    }

    public function tryResolve(RegistryKey|string $key): mixed
    {
        $derived = $this->tryKeyFor($key);

        return $derived === null ? null : $this->entries->tryResolve($derived);
    }

    /** @return list<OscalProjector> */
    public function matches(RegistryKey|string $key): array
    {
        $derived = $this->tryKeyFor($key);

        return $derived === null ? [] : $this->entries->matches($derived);
    }

    /** @return list<RegistryKey> */
    public function keys(): array
    {
        return $this->entries->keys();
    }

    public function unfiltered(): Registry
    {
        return $this->entries->unfiltered();
    }

    public function authorizeWith(?Authorizer $authorizer): static
    {
        $this->entries->authorizeWith($authorizer);

        return $this;
    }

    /**
     * Every key argument's one entrance: a class-string becomes its segment-mapped key, anything else
     * is already an address and passes through to the kernel's own door.
     */
    private function keyFor(RegistryKey|string $key): RegistryKey
    {
        return $this->tryKeyFor($key) ?? Key::parse((string) $key);
    }

    /**
     * The nullable half of {@see keyFor()}. A read fed by a request, a URL or stored data must be able
     * to answer "no" rather than raise `InvalidRegistryKey` before a miss is ever considered
     * (registry-kernel ticket 58).
     */
    private function tryKeyFor(RegistryKey|string $key): ?RegistryKey
    {
        if ($key instanceof RegistryKey) {
            return $key;
        }

        if (! str_contains($key, '\\') && ! class_exists($key)) {
            return Key::tryParse($key);
        }

        return Key::tryParse(implode(Key::SEPARATOR, array_map(
            fn (string $part): string => strtolower((string) preg_replace(
                '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/',
                '-',
                $part,
            )),
            explode('\\', ltrim($key, '\\')),
        )));
    }
}
