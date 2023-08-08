<?php

namespace Tokyo\Enums;

enum OperatingSystem: int
{
    case DARWIN = 0;
    case LINUX = 1;
    case WINDOWS = 2;
    case UNKNOWN = 3;
}
