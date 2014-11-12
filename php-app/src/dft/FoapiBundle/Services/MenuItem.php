<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 10/11/14
 * Time: 20:21
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;


class MenuItem {
    use ContainerAware;

    // SQL query type constants.
    const SELECT_MENU_ITEMS = 0x01;
    const COUNT_MENU_ITEMS = 0x02;

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
                  menu_items.id,
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
        if (array_key_exists('menu_items', $filters) && !is_null($filters["item_name"])) {
            $query .= " AND item_name LIKE ? ";
        }
        if (array_key_exists('category_id', $filters) &&!is_null($filters["category_id"])) {
            $query .= " AND category_id = ? ";
        }
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_MENU_ITEMS) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this
            ->getContainer()
            ->get('doctrine')
            ->getEntityManager()
            ->getConnection()
            ->prepare($query);

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
} 