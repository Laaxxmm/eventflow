<?php

namespace App\Enums;

/**
 * Sport packs. Each case has YAML defaults in events/sports/<value>.yaml.
 */
enum Sport: string
{
    case Skating = 'skating';
    case Cycling = 'cycling';
    case Running = 'running';
    case Generic = 'generic';
}
