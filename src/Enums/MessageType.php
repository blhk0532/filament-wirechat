<?php

namespace AdultDate\FilamentWirechat\Enums;

enum MessageType: string
{
    case TEXT = 'text';
    case ATTACHMENT = 'attachment';
}
