<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Exceptions\CortexException;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Keystone\Testing\Arch;

const CORTEX_DOMAINS = __DIR__.'/../src/Domains';

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Cortex')
    ->toUseStrictTypes();

// Atrium is suggested, not required: only src/Atrium names its classes, and
// CortexServiceProvider reaches that layer behind a class_exists() guard.
arch('only the Atrium layer uses Atrium')
    ->expect('RefactorCircus\Cortex')
    ->not->toUse('RefactorCircus\Atrium')
    ->ignoring('RefactorCircus\Cortex\Atrium');

arch('actions are final classes')
    ->expect(Arch::domainNamespaces('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Actions'))
    ->classes()
    ->toBeFinal();

arch('actions extend the Keystone Action base: callers use execute(), the action implements a protected handle()')
    ->expect(Arch::domainNamespaces('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Actions'))
    ->classes()
    ->toExtend(Action::class);

arch('every exception descends from CortexException')
    ->expect(['RefactorCircus\Cortex\Exceptions', ...Arch::domainNamespaces('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Exceptions')])
    ->classes()
    ->toExtend(CortexException::class)
    ->ignoring('RefactorCircus\Cortex\Exceptions\CortexException');

arch('actions take their input as Data objects, not arrays')
    ->expect(fn (): array => array_values(array_filter(
        array_map(function (string $action): ?string {
            foreach ((new ReflectionMethod($action, 'handle'))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && in_array($parameter->getName(), ['data', 'filters', 'input', 'attributes', 'values', 'tools'], true)) {
                    return $action.'::handle($'.$parameter->getName().')';
                }
            }

            return null;
        }, Arch::domainClasses('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Actions')),
    )))
    ->toBeEmpty();

arch('action input validates itself')
    ->expect(fn (): array => Arch::dataWithoutRules(Arch::domainClasses('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Data')))
    ->toBeEmpty();

arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => Arch::actionEventGaps(CORTEX_DOMAINS.'/*/Actions/*.php'))
    ->toBeEmpty();

arch('actions write inside a transaction')
    ->expect(fn (): array => Arch::writesOutsideTransactions(CORTEX_DOMAINS.'/*/Actions/*.php'))
    ->toBeEmpty();

arch('every model fires an event for every lifecycle hook')
    ->expect(fn (): array => Arch::modelEventGaps(Arch::domainClasses('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Models')))
    ->toBeEmpty();

arch('requests validate once, with the Data rules, and build from what they validated')
    ->expect(fn (): array => array_values(array_filter(
        [...(array) glob(CORTEX_DOMAINS.'/*/Http/Requests/*.php'), ...(array) glob(CORTEX_DOMAINS.'/*/Mcp/Requests/*.php')],
        fn (string $path): bool => str_contains((string) file_get_contents($path), 'Data::from('),
    )))
    ->toBeEmpty();

arch('every model has a policy in cortex.policies')
    ->expect(fn (): array => Arch::modelsWithoutPolicies(
        Arch::domainClasses('RefactorCircus\Cortex', CORTEX_DOMAINS, 'Models'),
        (require __DIR__.'/../config/cortex.php')['policies'],
    ))
    ->toBeEmpty();

arch('tables are named through Tables, never as literals')
    ->expect(fn (): array => Arch::literalTableNames(
        [...array_values(array_filter(
            array_map(fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src', FilesystemIterator::SKIP_DOTS)))),
            fn (string $path): bool => str_ends_with($path, '.php') && ! str_ends_with($path, 'Support/Tables.php'),
        )), ...(array) glob(__DIR__.'/../database/migrations/*.php')],
        array_values(Tables::DEFAULTS),
    ))
    ->toBeEmpty();
