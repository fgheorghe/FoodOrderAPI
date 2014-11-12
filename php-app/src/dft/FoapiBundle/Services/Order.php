<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 10/11/14
 * Time: 20:21
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;


class Order {
    use ContainerAware;

    // SQL query type constants.
    const SELECT_ORDERS = 0x01;
    const COUNT_ORDERS = 0x02;

    /**
     * Method used for fetching all orders for a given account id.
     */
    public function fetchAll($userId, $filters = array()) {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_ORDERS,
                    $filters
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_ORDERS,
                    $filters
            )
        );
    }

    // Method used for constructing query string, without filters.
    private function constructFetchAllSqlStatement($queryType) {
        $query = false;
        if ($queryType == self::COUNT_ORDERS) {
            $query = "SELECT
                   count(*) as total
           FROM
               orders
           WHERE
               user_id IN (?)";
        } elseif ($queryType == self::SELECT_ORDERS) {
            $query = 'SELECT
                  *,
                  ( SELECT name FROM users WHERE users.id = orders.user_id LIMIT 1 ) AS created_by,
                  ( total_price - total_price * discount / 100 ) AS final_price
                FROM
                  orders
                WHERE
                  user_id IN (?)';
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters) {
        $query = $this->constructFetchAllSqlStatement($queryType);

        // Apply filters.
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_ORDERS) {
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
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_ORDERS) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $queryType == self::SELECT_ORDERS ? $results : $results[0]["total"];
    }
} 