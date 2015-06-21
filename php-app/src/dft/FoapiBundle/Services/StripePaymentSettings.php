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
 * Class StripePaymentSettings.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class StripePaymentSettings
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching stripe payment settings.
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
                stripe_secret_key,
                stripe_publishable_key
                FROM stripe_payment_settings
                WHERE user_id = ? LIMIT 1";
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
     * Updates stripe payment settings.
     * @param $userId
     * @param $secretKey
     * @param $publishableKey
     */
    public function updateStripePaymentSettings($userId, $secretKey, $publishableKey) {
        // Prepare query.
        $query = "REPLACE INTO stripe_payment_settings SET
            stripe_secret_key = ?,
            stripe_publishable_key = ?,
            user_id = ?";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $secretKey);
        $statement->bindValue(2, $publishableKey);
        $statement->bindValue(3, $userId);

        // Execute.
        $statement->execute();
    }
}