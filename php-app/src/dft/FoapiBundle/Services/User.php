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
use dft\FoapiBundle\Services\Login;

/**
 * Class User.
 * NOTE: This class only handles Chef / Administrator user types. Printer users are handled by the printer class.
 * NOTE: All passwords send to methods in this class must be raw!
 * NOTE: Depends on the login service.
 * @package dft\FoapiBundle\Services
 */
class User {
    use ContainerAware;
    use Database;
    use Logger;

    // SQL query type constants.
    const SELECT_USERS = 0x01;
    const COUNT_USERS = 0x02;
    const SELECT_ONE = 0x03;

    // Insert or update query type constants.
    const INSERT_QUERY_TYPE = 0x01;
    const UPDATE_QUERY_TYPE = 0x02;

    // User role types.
    const ROLE_TYPE_PRINTER = 1;
    const ROLE_TYPE_DELIVERY = 2;
    const ROLE_TYPE_ADMINISTRATOR = 3;
    const ROLE_TYPE_CHEF = 4;

    // Which columns can be partially updated.
    private $PARTIAL_UPDATE_ATTRIBUTES = array(
        'name',
        'password',
        'role_id',
        'email',
        'active_yn'
    );

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
    private function constructFetchSqlStatement($queryType, $userId) {
        $query = false;
        if ($queryType == self::COUNT_USERS) {
            $query = "SELECT
                   count(*) as total
           FROM
               users
           WHERE
               parent_id IN (" . $this->constructUserIdsIn($userId) . ")";
        } elseif ($queryType == self::SELECT_USERS || $queryType == self::SELECT_ONE) {
            $query = 'SELECT
                  id,
                  name,
                  role_id,
                  email,
                  active_yn
                FROM
                  users
                WHERE
                  parent_id IN (' . $this->constructUserIdsIn($userId) . ')';

            // Apply limit 1 if selecting a single order.
            if ($queryType == self::SELECT_ONE) {
                $query .= " AND id = ? LIMIT 1 ";
            }
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters) {
        $query = $this->constructFetchSqlStatement($queryType, $userId);

        // Apply filters.
        if (array_key_exists('role_id', $filters) && !is_null($filters["role_id"]) && is_array($filters["role_id"])) {
            // Escape SQL IN part.
            array_walk($filters["role_id"], function($value) {
                    return (int) $value;
                });
            $query .= " AND role_id IN (" . implode(",", $filters["role_id"]) . ") ";
        }

        // Apply sorting.
        if ($queryType != self::COUNT_USERS) {
            $query .= " ORDER BY name ASC";
        }

        // Apply limit.
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_USERS) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind extra parameters.
        $i = 0;
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
    private function constructInsertOrUpdateSql($type, $userId) {
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
            $query .= " WHERE id = ? AND parent_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";
        } else {
            $query .= " ,active_yn = 1, parent_id = " . $userId;
        }

        return $query;
    }

    /**
     * Method used for creating or updating a user belonging to a given user.
     * TODO: Validate role ids and other parameters.r
     * NOTE: requires the login service, to encrypt the password.
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
        $query = $this->constructInsertOrUpdateSql($actionType, $userId);

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

    // Convenience method used for partially updating a user.
    // Can be one or more of attributes (columns).
    private function partialUpdate($userId, $attributes, $parentId = null) {
        // Prepare query.
        $query = "";

        // Begin constructing query bits.
        foreach ($attributes as $attributeName => $attributeValue) {
            if (in_array($attributeName, $this->PARTIAL_UPDATE_ATTRIBUTES)) {
                $query .= (empty($query) ? "" : ",") . " " . $attributeName . " = ? ";
            }
        }

        // Prepend update bit and append the user and parent id - if a parent id is set.
        if (is_null($parentId)) {
            $query = "UPDATE users SET " . $query . " WHERE id = ?";
        } else {
            $query = "UPDATE users SET " . $query . " WHERE id = ? AND parent_id IN (" . $this->constructUserIdsIn($userId) . ")";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        // Begin adding parameters.
        $i = 0;
        foreach ($attributes as $attributeName => $attributeValue) {
            if (in_array($attributeName, $this->PARTIAL_UPDATE_ATTRIBUTES)) {
                if ($attributeName == 'password') {
                    // Get the login service, and encrypt the password.
                    $loginService = $this->container->get('dft_foapi.login');
                    $attributeValue = $loginService->encryptPassword($attributeValue);
                }
                $statement->bindValue(++$i, $attributeValue);
            }
        }
        // Add parent and user id.
        $statement->bindValue(++$i, $userId);

        // Execute.
        $statement->execute();
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

    /**
     * Shortcut for activating a user.
     * @param $parentId
     * @param $userId
     */
    public function activateUser($parentId, $userId) {
        $this->partialUpdate($userId, array('active_yn' => 1), $parentId);
    }

    /**
     * Shortcut for changing a user role.
     * @param $parentId
     * @param $userId
     * @param $roleId
     */
    public function changeUserRole($parentId, $userId, $roleId) {
        $this->partialUpdate($userId, array('role_id' => $roleId), $parentId);
    }

    /**
     * Shortcut for changing a user password.
     * @param $parentId
     * @param $userId
     * @param $password
     */
    public function changeUserPassword($parentId, $userId, $password) {
        $this->partialUpdate($userId, array('password' => $password), $parentId);
    }

    /**
     * Shortcut for deactivating a user.
     * @param $parentId
     * @param $userId
     */
    public function deactivateUser($parentId, $userId) {
        $this->partialUpdate($userId, array('active_yn' => 0), $parentId);
    }

    /**
     * Select a single user.
     * @param $parentId
     * @param $userId
     * @return Mixed associative array if found or null if not.
     */
    public function fetchOne($parentId, $userId) {
        // Prepare query.
        $query = $this->constructFetchSqlStatement(self::SELECT_ONE, $userId);

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->execute();

        $results = $statement->fetchAll();

        return count($results) == 1 ? $results[0] : null;
    }

    /**
     * Change a logged in user password.
     * @param $userId
     * @param $newPassword
     * @param $currentPassword
     * @return bool
     */
    public function changeOwnPassword($userId, $newPassword, $currentPassword) {
        // Get the login service, to check current password.
        $loginService = $this->getContainer()->get('dft_foapi.login');

        if ($loginService->checkUserLoginBy(Login::GET_USER_BY_ID, $userId, $currentPassword)) {
            $this->changeUserPassword(
                // Null parent.
                null,
                $userId,
                $newPassword
            );

            // TODO: Check for errors.
            return true;
        }

        return false;
    }

    /**
     * Method used for fetching account ids belonging to a user.
     *
     * @param $parentId
     * @return array
     */
    public function getSubAccountIds($parentId) {
        $query = "SELECT id FROM users WHERE parent_id = ?";
        $statement = $this->prepare($query);

        $statement->bindValue(1, $parentId);
        $statement->execute();

        $results = $statement->fetchAll();

        return count($results) == 1 ? $results[0] : array();
    }

    /**
     * Method used for fetching the currently logged in user id, and sub account ids as an array.
     * @param $userId
     * @return mixed
     */
    public function getAuthenticatedUserIdAndSubAccountIds($userId = null) {
        // Get the logged in user id.
        $userId = is_null($userId) ? $this->container->get('dft_foapi.login')->getAuthenticatedUserId() : $userId;
        // Get sub account ids.
        $subAccountIds = $this->container->get('dft_foapi.user')->getSubAccountIds($userId);

        // Add the logged in user id and return.
        $subAccountIds[] = $userId;
        return $subAccountIds;
    }
} 