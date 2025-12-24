<?php

namespace RiseTechApps\Geonames;

use Exception;
use RiseTechApps\Geonames\Features\Countries;
use RiseTechApps\Geonames\Features\Country;
use RiseTechApps\Geonames\Features\Regions;

class Geonames
{

    public function regioes(): Regions
    {
        return new Regions();
    }

    public function countries(): Countries
    {
        return new Countries();
    }

    /**
     * @throws Exception
     */
    public function country(string $country): Country
    {
        return new Country($country);
    }
}
