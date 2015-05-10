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


class PrinterActivity {
    use ContainerAware;
    use Database;
    use Logger;

    // SQL query type constants.
    const SELECT_PRINTER_ACTIVITY = 0x01;
    const COUNT_PRINTER_ACTIVITY = 0x02;

    /**
     * Method used for fetching all activity log entries for a given account id.
     */
    public function fetchAll($userId, $filters = array()) {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_PRINTER_ACTIVITY,
                    $filters
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_PRINTER_ACTIVITY,
                    $filters
            )
        );
    }

    // Method used for constructing query string, without filters.
    private function constructFetchAllSqlStatement($queryType, $userId) {
        $query = false;
        if ($queryType == self::COUNT_PRINTER_ACTIVITY) {
            $query = "SELECT
                   count(*) as total
           FROM
               activity_log_entries
           WHERE
               user_id IN (" . $this->constructUserIdsIn($userId) . ")";
        } elseif ($queryType == self::SELECT_PRINTER_ACTIVITY) {
            $query = 'SELECT
                  users.name AS printer_identifier,
                  activity_log_entries.*
                FROM
                  activity_log_entries
                LEFT JOIN
                  users
                ON
                  users.id = activity_log_entries.user_id
                WHERE
                  user_id IN (' . $this->constructUserIdsIn($userId) . ')';
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters) {
        $query = $this->constructFetchAllSqlStatement($queryType, $userId);

        // Apply sorting.
        if ($queryType != self::COUNT_PRINTER_ACTIVITY) {
            $query .= " ORDER BY id DESC ";
        }

        // Apply filters.
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_PRINTER_ACTIVITY) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind extra parameters.
        $i = 0;
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_PRINTER_ACTIVITY) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $queryType == self::SELECT_PRINTER_ACTIVITY ? $results : $results[0]["total"];
    }
} 