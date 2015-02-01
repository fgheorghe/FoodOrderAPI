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

    // Method used for constructing query string.
    private function constructFetchAllSqlStatement() {
        return "SELECT * FROM discounts WHERE user_id IN (?) ORDER BY discount_type ASC, discount_name ASC";
    }

    // Method used for executing query.
    private function executeFetchAllStatement($userId) {
        // Get query.
        $query = $this->constructFetchAllSqlStatement();

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $this->constructUserIdsIn($userId));
        $statement->execute();

        // Return discounts.
        return $statement->fetchAll();
    }

    /**
     * Method used for creating a discount.
     * @param $userId
     * @param $discountType
     * @param $discountName
     * @param $value
     */
    public function createDiscount($userId, $discountType, $discountName, $value) {
        // Construct query.
        $query = "INSERT INTO discounts SET user_id = ?, discount_type = ?, discount_name = ?, value = ?";

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $discountType);
        $statement->bindValue(3, $discountName);
        $statement->bindValue(4, $value);

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
        $statement = $this->prepare("DELETE FROM discounts WHERE user_id IN(?) AND id = ? LIMIT 1");
        $statement->bindValue(1, $this->constructUserIdsIn($userId));
        $statement->bindValue(2, $discountId);

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
     */
    public function updateDiscount($userId, $discountId, $discountType, $discountName, $value) {
        // Construct query.
        $query = "UPDATE
            discounts
            SET
                discount_type = ?,
                discount_name = ?,
                value = ?
            WHERE
                user_id IN (?)
                AND id = ?
            LIMIT 1";

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $discountType);
        $statement->bindValue(2, $discountName);
        $statement->bindValue(3, $value);
        $statement->bindValue(4, $this->constructUserIdsIn($userId));
        $statement->bindValue(5, $discountId);

        // Execute.
        $statement->execute();
    }
}