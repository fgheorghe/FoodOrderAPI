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

class FrontEndDiscounts {
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching all discounts. Unlike other fetchAll methods, this does not produce pagiantion.
     * @param $userId
     * @return array
     */
    public function fetchAll($userId) {
        return $this->executeFetchAllStatement(
            $userId
        );
    }

    /**
     * Method used for fetching a single discount.
     * @param $discountId
     * @param $userId
     * @return Array
     */
    public function fetchOne($discountId, $userId) {
        $sql = "SELECT * FROM front_end_discounts WHERE user_id IN (" . $this->constructUserIdsIn($userId) . ") AND id = ? LIMIT 1";
        $statement = $this->prepare($sql);
        $statement->bindValue(1, $discountId);
        $statement->execute();
        $result = $statement->fetchAll();
        return count($result) == 1 ? $result[0] : null;
    }

    // Method used for constructing query string.
    private function constructFetchAllSqlStatement($userId) {
        return "SELECT * FROM front_end_discounts WHERE user_id IN (" . $this->constructUserIdsIn($userId) . ") ORDER BY discount_type ASC, discount_name ASC";
    }

    // Method used for executing query.
    private function executeFetchAllStatement($userId) {
        // Get query.
        $query = $this->constructFetchAllSqlStatement($userId);

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->execute();

        // Return discounts.
        return $statement->fetchAll();
    }

    /**
     * Method used for creating a discount.
     * @param $userId
     * @param $userIds
     * @param $discountType
     * @param $discountName
     * @param $value
     * @param $discountItemId
     * @param $discountCategoryItems PHP Array of id and category_name pairs. Stored as json encoded value. May be null.
     */
    public function createDiscount($userId, $userIds, $discountType, $discountName, $value, $discountItemId,
                                   $discountCategoryItems) {
        // Construct query.
        $query = "INSERT INTO front_end_discounts SET user_id = ?, discount_type = ?, discount_name = ?, value = ?, discount_item_id = ?";

        // If a discount item is added, then add the name of that item as well.
        if (!is_null($discountItemId)) {
            $query .= " ,discount_item_name = (SELECT item_name FROM menu_items WHERE id = ? and user_id IN (" . $this->constructUserIdsIn($userIds) . ") LIMIT 1)";
        }
        if (!is_null($discountCategoryItems)) {
            $query .= " ,discount_category_items = ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $discountType);
        $statement->bindValue(3, $discountName);
        $statement->bindValue(4, $value);
        $statement->bindValue(5, $discountItemId);

        // If a discount item is added, then add the name of that item as well.
        $i = 6;
        if (!is_null($discountItemId)) {
            $statement->bindValue($i, $discountItemId);
            $i++;
        }
        if (!is_null($discountCategoryItems)) {
            // NOTE: Discount categories are stored as JSON encoded strings.
            // TODO: Review all logic for bundle discounts.
            $statement->bindValue($i, json_encode($discountCategoryItems));
            $i++;
        }

        // Execute.
        $statement->execute();
    }

    /**
     * Deletes a discount.
     * @param $userId
     * @param $discountId
     */
    public function deleteDiscount($userId, $discountId) {
        // Prepare statement.
        $statement = $this->prepare("DELETE FROM front_end_discounts WHERE user_id IN(" . $this->constructUserIdsIn($userId) . ") AND id = ? LIMIT 1");
        $statement->bindValue(1, $discountId);

        // Execute.
        $statement->execute();
    }

    /**
     * Updates a discount.
     * @param $userId
     * @param $discountId
     * @param $discountType
     * @param $discountName
     * @param $value
     * @param $discountItemId
     * @param $discountCategoryItems PHP Array of id and category_name pairs. Stored as json encoded value. May be null.
     */
    public function updateDiscount($userId, $discountId, $discountType, $discountName, $value, $discountItemId,
                                   $discountCategoryItems) {
        // Construct query.
        $query = "UPDATE
                front_end_discounts
            SET
                discount_type = ?,
                discount_name = ?,
                value = ?,
                discount_item_id = ?
                " . (!is_null($discountItemId) ? ",discount_item_name = (SELECT item_name FROM menu_items WHERE id = ? and user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1)" : "") . "
                " . (!is_null($discountCategoryItems) ? ",discount_category_items = ?" : "") . "
            WHERE
                user_id IN (" . $this->constructUserIdsIn($userId) . ")
                AND id = ?
            LIMIT 1";

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $discountType);
        $statement->bindValue(2, $discountName);
        $statement->bindValue(3, $value);
        $statement->bindValue(4, $discountItemId);
        $i = 5;
        // If a discount item is added, then add the name of that item as well.
        if (!is_null($discountItemId)) {
            $statement->bindValue($i++, $discountItemId);
        }
        if (!is_null($discountCategoryItems)) {
            // NOTE: Discount categories are stored as JSON encoded strings.
            // TODO: Review all logic for bundle discounts.
            $statement->bindValue($i, json_encode($discountCategoryItems));
            $i++;
        }
        $statement->bindValue($i, $discountId);

        // Execute.
        $statement->execute();
    }
}