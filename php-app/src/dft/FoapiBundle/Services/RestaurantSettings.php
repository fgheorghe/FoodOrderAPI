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

/**
 * Class RestaurantSettings
 * @package dft\FoapiBundle\Services
 */
class RestaurantSettings {
    use ContainerAware;
    use Database;

    /**
     * Method used for fetching restaurant settings.
     * @param $userId
     * @return null
     */
    public function fetchOne($userId) {
        return $this->executeFetchOneStatement($userId);
    }

    // Convenience method used for constructing the select query.
    private function constructFetchOneSqlStatement() {
        return "SELECT
                restaurant_name,
                restaurant_post_code,
                delivery_range,
                opening_time,
                closing_time,
                open_all_day
                FROM restaurant_settings WHERE user_id = ? LIMIT 1";
    }

    // Method used for executing query.
    private function executeFetchOneStatement($userId) {
        $query = $this->constructFetchOneSqlStatement();

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);

        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) == 1 ? $results[0] : null;
    }
} 