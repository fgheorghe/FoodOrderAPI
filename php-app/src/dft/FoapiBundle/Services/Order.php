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
        if (array_key_exists('order_type', $filters) && !is_null($filters["order_type"])) {
            $query .= " AND order_type = ? ";
        }
        if (array_key_exists('delivery_type', $filters) && !is_null($filters["delivery_type"])) {
            $query .= " AND delivery_type = ? ";
        }
        if (array_key_exists('customer_type', $filters) && !is_null($filters["customer_type"])) {
            $query .= " AND customer_type = ? ";
        }
        if (array_key_exists('payment_status', $filters) && !is_null($filters["payment_status"])) {
            $query .= " AND payment_status = ? ";
        }
        if (array_key_exists('customer_name', $filters) && !is_null($filters["customer_name"])) {
            $query .= " AND customer_name LIKE ? ";
        }
        if (array_key_exists('phone_number', $filters) && !is_null($filters["phone_number"])) {
            $query .= " AND phone_number LIKE ? ";
        }

        if (array_key_exists('interval', $filters) && !is_null($filters["interval"])) {
            switch ($filters["interval"]) {
                case "today":
                    $query .= " AND create_date > DATE_SUB(NOW(), INTERVAL 1 DAY) ";
                    break;
                default:
                    // Do nothing.
                    break;
            }
        }

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
        if (array_key_exists('order_type', $filters) && !is_null($filters["order_type"])) {
            $statement->bindValue(++$i, $filters['order_type']);
        }
        if (array_key_exists('delivery_type', $filters) && !is_null($filters["delivery_type"])) {
            $statement->bindValue(++$i, $filters['delivery_type']);
        }
        if (array_key_exists('customer_type', $filters) && !is_null($filters["customer_type"])) {
            $statement->bindValue(++$i, $filters['customer_type']);
        }
        if (array_key_exists('payment_status', $filters) && !is_null($filters["payment_status"])) {
            $statement->bindValue(++$i, $filters['payment_status']);
        }
        if (array_key_exists('customer_name', $filters) && !is_null($filters["customer_name"])) {
            $statement->bindValue(++$i, "%" . $filters['customer_name'] . "%");
        }
        if (array_key_exists('phone_number', $filters) && !is_null($filters["phone_number"])) {
            $statement->bindValue(++$i, "%" . $filters['phone_number'] . "%");
        }

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