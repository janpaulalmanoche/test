<?php

namespace Vendor\Veeroll\Facades;

use Illuminate\Support\Facades\Facade;

class Veeroll extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'veeroll';
    }
}
