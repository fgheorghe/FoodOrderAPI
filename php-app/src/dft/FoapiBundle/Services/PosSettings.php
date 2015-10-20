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
 * Class PosSettings.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class PosSettings
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching pos settings.
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
                query_interval,
                printer_device_path
                FROM pos_settings WHERE user_id = ? LIMIT 1";
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
     * Updates pos settings.
     * @param $userId
     * @param $queryInterval
     * @param $printerDevicePath
     */
    public function updatePosSettings($userId, $queryInterval, $printerDevicePath) {
        // Prepare query.
        $query = "REPLACE INTO pos_settings SET
            user_id = ?,
            query_interval = ?,
            printer_device_path = ?";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $queryInterval);
        $statement->bindValue(3, $printerDevicePath);

        // Execute.
        $statement->execute();
    }
}