<?php

namespace AdultDate\FilamentWirechat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string formatTableName(string $table)
 */
class Wirechat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wirechat';
    }
}
