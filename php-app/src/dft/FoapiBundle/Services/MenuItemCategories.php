<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 10/11/14
 * Time: 20:21
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;


class MenuItemCategories {
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching all menu item categories.
     * @param $userId Array Although menu item categories are the same for all users,
     * filters will require this parameter.
     * @param $filters Array Optional filters. Most will require a valid user id. An
     * example is non_empty - if set to 1, it will only return menu item categories
     * with menu items. If this parameter is set, the service will include an item_count
     * in the response object.
     * @return Array
     */
    public function fetchAll($userId, $filters = array()) {
        return $this->executeFetchAllStatement($userId, $filters);
    }

    // Method used for executing query.
    private function executeFetchAllStatement($userId, $filters) {
        // Prepare base SQL statement.
        $query = "SELECT * FROM menu_item_categories";

        // Apply 'non_empty' filter.
        if (array_key_exists("non_empty", $filters) && !is_null($filters["non_empty"]) && $filters["non_empty"] == 1) {
            $query = "SELECT
                    *,
                    (SELECT
                        COUNT(*)
                    FROM
                        menu_items
                    WHERE
                        menu_items.category_id = menu_item_categories.id
                        AND menu_items.user_id IN (?)
                    ) AS item_count
                FROM menu_item_categories
                HAVING item_count > 0";
        }

        // Apply sorting.
        $query = $query . " ORDER BY category_name ASC";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Apply 'non_empty' filter.
        if (array_key_exists('non_empty', $filters) && !is_null($filters["non_empty"]) && $filters["non_empty"] == 1) {
            $statement->bindValue(1, $this->constructUserIdsIn($userId));
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $results;
    }
} 