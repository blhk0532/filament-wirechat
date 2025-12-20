<?php

namespace AdultDate\FilamentWirechat\Enums;

enum ParticipantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case PARTICIPANT = 'participant';
}
