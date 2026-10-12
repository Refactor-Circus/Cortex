<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Tests;

use RefactorCircus\Cortex\Support\Tables;

/**
 * Every Cortex table renamed through `cortex.tables` before migrating.
 */
abstract class RenamedTablesTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        foreach (array_keys(Tables::DEFAULTS) as $key) {
            $app['config']->set('cortex.tables.'.$key, 'crm_'.$key);
        }
    }
}
