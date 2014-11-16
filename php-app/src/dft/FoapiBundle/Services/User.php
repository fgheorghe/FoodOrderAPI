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


class User {
    use ContainerAware;
    use Database;

    // SQL query type constants.
    const SELECT_USERS = 0x01;
    const COUNT_USERS = 0x02;

    // Insert or update query type constants.
    const INSERT_QUERY_TYPE = 0x01;
    const UPDATE_QUERY_TYPE = 0x02;

    /**
     * Method used for fetching all users for a given account id.
     */
    public function fetchAll($userId, $filters = array()) {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_USERS,
                    $filters
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_USERS,
                    $filters
            )
        );
    }

    // Method used for constructing query string, without filters.
    private function constructFetchAllSqlStatement($queryType) {
        $query = false;
        if ($queryType == self::COUNT_USERS) {
            $query = "SELECT
                   count(*) as total
           FROM
               users
           WHERE
               parent_id IN (?)";
        } elseif ($queryType == self::SELECT_USERS) {
            $query = 'SELECT
                  id,
                  name,
                  role_id,
                  email
                FROM
                  users
                WHERE
                  parent_id IN (?)';
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters) {
        $query = $this->constructFetchAllSqlStatement($queryType);

        // Apply filters.
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_USERS) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->bindValue(1, $userId);

        // Bind extra parameters.
        $i = 1;
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_USERS) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $queryType == self::SELECT_USERS ? $results : $results[0]["total"];
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
        $query .= " users
            SET
              name = ?,
              role_id = ?,
              email = ?,
              password = ?";

        if ($type == self::UPDATE_QUERY_TYPE) {
            $query .= " WHERE id = ? AND parent_id IN (?) LIMIT 1";
        } else {
            $query .= " ,parent_id = ?";
        }

        return $query;
    }

    /**
     * Method used for creating or updating a user belonging to a given user.
     * TODO: Validate role ids and other parameters.r
     * Note: requires the login service, to encrypt the password.
     * @param $actionType
     * @param $userId
     * @param $name
     * @param $roleId
     * @param $email
     * @param $password
     * @param $parentId
     */
    private function createOrUpdate($actionType, $userId, $name, $roleId, $email, $password,
        $parentId = null) {
        // Prepare query.
        $query = $this->constructInsertOrUpdateSql($actionType);

        // Get the login service, and encrypt the password.
        $loginService = $this->container->get('dft_foapi.login');
        $password = $loginService->encryptPassword($password);

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind params.
        $statement->bindValue(1, $name);
        $statement->bindValue(2, $roleId);
        $statement->bindValue(3, $email);
        $statement->bindValue(4, $password);
        $statement->bindValue(5, $userId);

        if ($actionType == self::UPDATE_QUERY_TYPE) {
            $statement->bindValue(6, $parentId);
        }

        // Finally, create the user.
        $statement->execute();
    }

    /**
     * Method used for creating a user belonging to a given user.
     * @param $userId
     * @param $name
     * @param $roleId
     * @param $email
     * @param $password
     */
    public function createUser($userId, $name, $roleId, $email, $password) {
        $this->createOrUpdate(
            self::INSERT_QUERY_TYPE,
            $userId,
            $name,
            $roleId,
            $email,
            $password
        );
    }


    /**
     * Method used for updating a user belonging to a given parent user id.
     * @param $parentId
     * @param $userId
     * @param $name
     * @param $roleId
     * @param $email
     * @param $password
     */
    public function updateUser($parentId, $userId, $name, $roleId, $email, $password) {
        $this->createOrUpdate(
            self::UPDATE_QUERY_TYPE,
            $userId,
            $name,
            $roleId,
            $email,
            $password,
            $parentId
        );
    }
} 