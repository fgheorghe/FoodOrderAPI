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
 * Class FrontEndSettings.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class FrontEndSettings
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching front end settings.
     * @param $userId
     * @return null
     */
    public function fetchOne($userId)
    {
        return $this->executeFetchOneStatement($userId);
    }

    // Convenience method used for constructing the select query.
    private function constructFetchOneSqlStatement()
    {
        return "SELECT
                restaurant_description,
                phone_numbers,
                facebook_page_url,
                google_page_url,
                twitter_page_url,
                full_address,
                info_text
                FROM front_end WHERE user_id = ? LIMIT 1";
    }

    // Method used for executing query.
    private function executeFetchOneStatement($userId)
    {
        $query = $this->constructFetchOneSqlStatement();

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);

        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) == 1 ? $results[0] : null;
    }

    /**
     * Updates front end settings.
     * @param $userId
     * @param $restaurantDescription
     * @param $phoneNumbers
     * @param $facebookPageUrl
     * @param $googlePageUrl
     * @param $twitterPageUrl
     * @param $fullAddress
     * @param $infoText
     */
    public function updateFrontEndSettings($userId, $restaurantDescription, $phoneNumbers, $facebookPageUrl,
        $googlePageUrl, $twitterPageUrl, $fullAddress, $infoText) {
        // Prepare query.
        $query = "REPLACE INTO front_end SET
            restaurant_description = ?,
            phone_numbers = ?,
            facebook_page_url = ?,
            google_page_url = ?,
            twitter_page_url = ?,
            full_address = ?,
            info_text = ?,
            user_id = ?";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $restaurantDescription);
        $statement->bindValue(2, $phoneNumbers, \PDO::PARAM_STR);
        $statement->bindValue(3, $facebookPageUrl);
        $statement->bindValue(4, $googlePageUrl);
        $statement->bindValue(5, $twitterPageUrl);
        $statement->bindValue(6, $fullAddress);
        $statement->bindValue(7, $infoText);
        $statement->bindValue(8, $userId);

        // Execute.
        $statement->execute();
    }
}