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


class MenuItem {
    use ContainerAware;
    use Database;
    use Logger;

    // Select SQL query type constants.
    const SELECT_MENU_ITEMS = 0x01;
    const COUNT_MENU_ITEMS = 0x02;

    // Insert or update query type constants.
    const INSERT_QUERY_TYPE = 0x01;
    const UPDATE_QUERY_TYPE = 0x02;

    /**
     * Method used for fetching all menu items for a given account id.
     */
    public function fetchAll($userId, $filters = array()) {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_MENU_ITEMS,
                    $filters
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_MENU_ITEMS,
                    $filters
            )
        );
    }

    // Method used for constructing query string, without filters.
    private function constructFetchAllSqlStatement($queryType) {
        $query = false;
        if ($queryType == self::COUNT_MENU_ITEMS) {
            $query = "SELECT
                   count(*) as total
           FROM
               menu_items
           WHERE
               user_id IN (?)";
        } elseif ($queryType == self::SELECT_MENU_ITEMS) {
            $query = 'SELECT
                  id,
                  item_name,
                  size_id,
                  price,
                  category_id,
                  item_number
                FROM
                  menu_items
                WHERE
                  user_id IN (?)';
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters) {
        $query = $this->constructFetchAllSqlStatement($queryType);

        // Apply filters.
        if (array_key_exists('item_name', $filters) && !is_null($filters["item_name"])) {
            $query .= " AND item_name LIKE ? ";
        }
        if (array_key_exists('category_id', $filters) &&!is_null($filters["category_id"])) {
            $query .= " AND category_id = ? ";
        }

        // Apply sorting.
        $query .= " ORDER BY
            item_name,
            item_number ASC";

        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_MENU_ITEMS) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->bindValue(1, $userId);

        // Bind extra parameters.
        $i = 1;
        if (array_key_exists('item_name', $filters) && !is_null($filters["item_name"])) {
            $statement->bindValue(++$i, "%" . $filters['item_name'] . "%");
        }
        if (array_key_exists('category_id', $filters) && !is_null($filters["category_id"])) {
            $statement->bindValue(++$i, $filters['category_id']);
        }
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_MENU_ITEMS) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $queryType == self::SELECT_MENU_ITEMS ? $results : $results[0]["total"];
    }

    /**
     * Method used for deleting food menu items.
     * TODO: Return a success value.
     * @param $userId
     * @param $menuItemId
     */
    public function deleteMenuItem($userId, $menuItemId) {
        // Prepare query.
        $query = "DELETE FROM menu_items WHERE user_id IN (?) and id = ? LIMIT 1";

        // Delete item.
        $statement = $this->prepare($query);

        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $menuItemId);

        $statement->execute();
    }

    // Convenience method used for creating the INSERT or UPDATE SQL statement.
    private function constructInsertOrUpdateSql($type) {
        switch ($type) {
            case self::INSERT_QUERY_TYPE:
                $query = "INSERT INTO";
                break;
            case self::UPDATE_QUERY_TYPE:
                $query = "UPDATE";
                break;
            default:
                throw new Exception("Invalid query type. See class documentation");
                break;
        }

        // Prepare SQL query.
        $query .= "
              menu_items
            SET
              item_number = ?,
              category_id = ?,
              item_name = ?,
              size_id = ?,
              price = ?";

        if ($type == self::UPDATE_QUERY_TYPE) {
            $query .= " WHERE user_id IN (?) AND id = ? LIMIT 1";
        } else {
            $query .= " ,user_id = ?";
        }

        return $query;
    }

    // Convenience method used for creating or updating a record.
    // TODO: Same as for deleteMenuItem and verify if size and category id are valid.
    private function createOrUpdate($actionType, $userId, $itemNumber, $categoryId, $itemName, $sizeId, $price,
        $menuItemId = null) {
        $query = $this->constructInsertOrUpdateSql($actionType);

        // Insert.
        $statement = $this->prepare($query);

        // Bind params.
        $statement->bindValue(1, $itemNumber);
        $statement->bindValue(2, $categoryId);
        $statement->bindValue(3, $itemName);
        $statement->bindValue(4, $sizeId);
        $statement->bindValue(5, $price);
        $statement->bindValue(6, $userId);

        if ($actionType == self::UPDATE_QUERY_TYPE) {
            $statement->bindValue(7, $menuItemId);
        }

        // Persist.
        $statement->execute();
    }

    /**
     * Method used for creating a food menu item. Wrapper for createOrUpdate.
     * @param $userId
     * @param $itemNumber
     * @param $categoryId
     * @param $itemName
     * @param $sizeId
     * @param $price
     */
    public function createMenuItem($userId, $itemNumber, $categoryId, $itemName, $sizeId, $price) {
        $this->createOrUpdate(
            self::INSERT_QUERY_TYPE,
            $userId,
            $itemNumber,
            $categoryId,
            $itemName,
            $sizeId,
            $price
        );
    }

    /**
     * Method used for updating a food menu item. Wrapper for createOrUpdate.
     * @param $userId
     * @param $menuItemId
     * @param $itemNumber
     * @param $categoryId
     * @param $itemName
     * @param $sizeId
     * @param $price
     */
    public function updateMenuItem($userId, $menuItemId, $itemNumber, $categoryId, $itemName, $sizeId, $price) {
        $this->createOrUpdate(
            self::UPDATE_QUERY_TYPE,
            $userId,
            $itemNumber,
            $categoryId,
            $itemName,
            $sizeId,
            $price,
            $menuItemId
        );
    }

    /**
     * Method used for fetching an item, for a given user.
     *
     * @param $itemId
     * @param $userId
     * @return bool|string
     */
    public function fetchOne($itemId, $userId) {
        // Prepare query.
        $query = "SELECT * FROM menu_items WHERE id = ?";

        // If a user is present, then apply it.
        if (!is_null($userId)) {
            $query .= " AND user_id = ?";
        }

        // Apply limit.
        $query .= " LIMIT 1";

        // ...and execute.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $itemId);

        if (!is_null($userId)) {
            $statement->bindValue(2, $userId);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) == 1 ? $results[0] : null;
    }
} 