<?php

if (! function_exists('wirechat')) {
    function wirechat(): \AdultDate\FilamentWirechat\Services\WirechatService
    {
        return app('wirechat');
    }
}
