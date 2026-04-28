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

    /**
     * @deprecated Use regioes() em português ou regions()
     */
    public function regions(): Regions
    {
        return new Regions();
    }

    /**
     * @throws Exception
     */
    public function region(int $id): ?\RiseTechApps\Geonames\Features\Region
    {
        return (new Regions())->find($id);
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
