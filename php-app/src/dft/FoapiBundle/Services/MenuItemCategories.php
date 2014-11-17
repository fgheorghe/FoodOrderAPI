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


class MenuItemCategories {
    use ContainerAware;
    use Database;

    /**
     * Method used for fetching all menu item categories.
     */
    public function fetchAll() {
        return $this->executeFetchAllStatement();
    }

    // Method used for executing query.
    private function executeFetchAllStatement() {
        $query = "SELECT * FROM menu_item_categories ORDER BY category_name ASC";

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->execute();
        $results = $statement->fetchAll();

        return $results;
    }
} 