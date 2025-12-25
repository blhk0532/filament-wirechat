<?php

if (! function_exists('wirechat')) {
    /**
     * Get the Wirechat service instance.
     */
    function wirechat()
    {
        return \Adultdate\Wirechat\Facades\Wirechat::getFacadeRoot();
    }
}

if (! function_exists('wirechatColor')) {
    /**
     * Get the Wirechat Color service instance.
     */
    function wirechatColor()
    {
        return \Adultdate\Wirechat\Facades\WirechatColor::getFacadeRoot();
    }
}
