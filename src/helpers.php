<?php

declare(strict_types=1);

if (!function_exists('geonames')) {
    function geonames(): \RiseTechApps\Geonames\Geonames
    {
        return new \RiseTechApps\Geonames\Geonames();
    }
}
