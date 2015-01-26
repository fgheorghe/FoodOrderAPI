<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 18/11/14
 * Time: 19:27
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;


/**
 * Class ServiceCoverage.
 * Relies on google maps to calculate distance between two post codes.
 * @package dft\FoapiBundle\Services
 */
class ServiceCoverage
{
    use ContainerAware;
    use Database;
    use Logger;

    // Configuration.
    const GOOGLE_API_URL = "http://maps.googleapis.com/maps/api/directions/xml?sensor=false&origin=%s&destination=%s";

    // As per: http://stackoverflow.com/questions/2296087/using-php-and-google-maps-api-to-work-out-distance-between-2-post-codes-uk
    private function getDistance($from, $to) {
        $xml = @simplexml_load_file(sprintf(self::GOOGLE_API_URL, $from, $to));

        // Check if success.
        if ($xml->status == "ZERO_RESULTS") {
            return false;
        }

        $distance = (string) $xml->route->leg->distance->text;
        // Convert to miles and return.
        return (double) str_replace(" km", "", $distance) * 0.621371192;
    }

    /**
     * Checks if the customer post code is within the range of delivery for the restaurant.
     * @param $restaurantPostCode
     * @param $customerPostCode
     * @param $deliveryRange
     * @return bool
     */
    public function isCovered($restaurantPostCode, $customerPostCode, $deliveryRange) {
        $distance = $this->getDistance(
            $restaurantPostCode,
            $customerPostCode
        );

        if ($distance === false) {
            return false;
        }
        return $deliveryRange >= $distance;
    }
}
?>