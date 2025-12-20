<?php

namespace AdultDate\FilamentWirechat\Enums;

enum ConversationType: string
{
    case SELF = 'self';
    case PRIVATE = 'private';
    case GROUP = 'group';
}
