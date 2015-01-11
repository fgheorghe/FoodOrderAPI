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
 * Class BarclaysPaymentSettings.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class BarclaysPaymentSettings
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching barclays payment settings.
     * NOTE: Returns the domain_name restaurant settings value, for a client to construct the return url.
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
                pspid,
                sha1,
                live_payment_system,
                domain_name
                FROM barclays_payment_settings
                LEFT JOIN restaurant_settings
                ON barclays_payment_settings.user_id = restaurant_settings.user_id
                WHERE barclays_payment_settings.user_id = ? LIMIT 1";
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
     * Updates barclays payment settings.
     * @param $userId
     * @param $pspid
     * @param $sha1
     * @param $livePaymentSystem
     */
    public function updateBarclaysPaymentSettings($userId, $pspid, $sha1, $livePaymentSystem) {
        // Prepare query.
        $query = "REPLACE INTO barclays_payment_settings SET
            pspid = ?,
            sha1 = ?,
            live_payment_system = ?,
            user_id = ?";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $pspid);
        $statement->bindValue(2, $sha1);
        $statement->bindValue(3, $livePaymentSystem);
        $statement->bindValue(4, $userId);

        // Execute.
        $statement->execute();
    }
}