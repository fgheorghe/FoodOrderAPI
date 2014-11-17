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

class Customer {
    use ContainerAware;
    use Database;

    // SQL query type constants.
    const SELECT_CUSTOMERS = 0x01;
    const COUNT_CUSTOMERS = 0x02;

    // Insert or update query type constants.
    const INSERT_QUERY_TYPE = 0x01;
    const UPDATE_QUERY_TYPE = 0x02;

    // Which columns can be partially updated.
    private $PARTIAL_UPDATE_ATTRIBUTES = array(
        'verified'
    );

    /**
     * Method used for fetching all customers for a given account id.
     */
    public function fetchAll($userId, $filters = array()) {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_CUSTOMERS,
                    $filters
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_CUSTOMERS,
                    $filters
            )
        );
    }

    // Method used for constructing query string, without filters.
    private function constructFetchAllSqlStatement($queryType) {
        $query = false;
        if ($queryType == self::COUNT_CUSTOMERS) {
            $query = "SELECT
                   count(*) as total
           FROM
               customers
           WHERE
               user_id IN (?)";
        } elseif ($queryType == self::SELECT_CUSTOMERS) {
            $query = 'SELECT
                  id,
                  name,
                  email,
                  post_code,
                  address,
                  phone_number,
                  create_date,
                  verified,
                  user_id
                FROM
                  customers
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
            $queryType != self::COUNT_CUSTOMERS) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->bindValue(1, $userId);

        // Bind extra parameters.
        $i = 1;
        if (array_key_exists('start', $filters) && !is_null($filters["start"]) &&
            array_key_exists('limit', $filters) && !is_null($filters["limit"]) &&
            $queryType != self::COUNT_CUSTOMERS) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $queryType == self::SELECT_CUSTOMERS ? $results : $results[0]["total"];
    }

    // Convenience method used for partially updating a customer.
    // Can be one or more of attributes (columns).
    private function partialUpdate($userId, $customerId, $attributes) {
        // Prepare query.
        $query = "";

        // Begin constructing query bits.
        foreach ($attributes as $attributeName => $attributeValue) {
            if (in_array($attributeName, $this->PARTIAL_UPDATE_ATTRIBUTES)) {
                $query .= (empty($query) ? "" : ",") . " " . $attributeName . " = ? ";
            }
        }

        // Prepend update bit and append the user and parent id.
        $query = "UPDATE customers SET " . $query . " WHERE id = ? AND user_id IN (?)";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Begin adding parameters.
        $i = 0;
        foreach ($attributes as $attributeName => $attributeValue) {
            if (in_array($attributeName, $this->PARTIAL_UPDATE_ATTRIBUTES)) {
                $statement->bindValue(++$i, $attributeValue);
            }
        }

        // Add customer and user ids.
        $statement->bindValue(++$i, $customerId);
        $statement->bindValue(++$i, $userId);

        // Execute.
        $statement->execute();
    }

    /**
     * Shortcut used for verifying a customer.
     * @param $userId
     * @param $customerId
     */
    public function verifyCustomer($userId, $customerId) {
        $this->partialUpdate(
            $userId,
            $customerId,
            array( 'verified' => 1 )
        );
    }

    /**
     * Shortcut used for ...unverifying a customer.
     * @param $userId
     * @param $customerId
     */
    public function unverifyCustomer($userId, $customerId) {
        $this->partialUpdate(
            $userId,
            $customerId,
            array( 'verified' => 0 )
        );
    }

    // Construct SQL query.
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
              customers
            SET
              name = ?,
              email = ?,
              post_code = ?,
              address = ?,
              phone_number = ?,
              create_date = NOW(),
              verified = ?";

        if ($type == self::UPDATE_QUERY_TYPE) {
            $query .= " WHERE user_id IN (?) AND id = ? LIMIT 1";
        } else {
            $query .= " ,user_id = ?";
        }

        return $query;
    }

    // Create or update a customer.
    private function createOrUpdate($actionType, $userId, $name, $email, $postCode, $address,
        $phoneNumber, $verified, $customerId = null) {
        $query = $this->constructInsertOrUpdateSql($actionType);

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind params.
        $statement->bindParam(1, $name);
        $statement->bindParam(2, $email);
        $statement->bindParam(3, $postCode);
        $statement->bindParam(4, $address);
        $statement->bindParam(5, $phoneNumber);
        $statement->bindParam(6, $verified);
        $statement->bindParam(7, $userId);

        if ($actionType === self::UPDATE_QUERY_TYPE) {
            $statement->bindValue(8, $customerId);
        }

        // Persist.
        $statement->execute();
    }

    /**
     * Method used for creating a customer for a given user id.
     * @param $userId
     * @param $name
     * @param $email
     * @param $postCode
     * @param $address
     * @param $phoneNumber
     * @param $verified
     */
    public function createCustomer($userId, $name, $email, $postCode, $address,
        $phoneNumber, $verified) {
        $this->createOrUpdate(
            self::INSERT_QUERY_TYPE,
            $userId,
            $name,
            $email,
            $postCode,
            $address,
            $phoneNumber,
            $verified
        );
    }

    /**
     * Method used for updating an existing customer belonging to a given user id.
     * @param $userId
     * @param $customerId
     * @param $name
     * @param $email
     * @param $postCode
     * @param $address
     * @param $phoneNumber
     * @param $verified
     */
    public function updateCustomer($userId, $customerId, $name, $email, $postCode, $address,
        $phoneNumber, $verified) {
        $this->createOrUpdate(
            self::UPDATE_QUERY_TYPE,
            $userId,
            $name,
            $email,
            $postCode,
            $address,
            $phoneNumber,
            $verified,
            $customerId
        );
    }
}