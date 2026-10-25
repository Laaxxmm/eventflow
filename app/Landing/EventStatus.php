<?php

namespace App\Landing;

/**
 * Values of events.status.
 */
final class EventStatus
{
    public const Draft = 'draft';

    public const Published = 'published';

    public const Closed = 'closed';

    public const ALL = [self::Draft, self::Published, self::Closed];
}
