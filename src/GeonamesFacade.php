<?php

namespace RiseTechApps\Geonames;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RiseTechApps\Geonames\Skeleton\SkeletonClass
 */
class GeonamesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'geonames';
    }
}
