<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 16/11/14
 * Time: 12:49
 */

namespace dft\FoapiBundle\Traits;

/**
 * Class Database. Assumes the target class has a container property set!
 * @package dft\FoapiBundle\Traits
 */
trait Database {
    // Fetches the database connection.
    public function getConnection() {
        return $this->getContainer()->get('doctrine')->getManager()->getConnection();
    }
    /**
     * Convenience method used for preparing a statement, using Doctrine.
     * @param $query String SQL query to prepare.
     * @return \Doctrine\DBAL\Driver\Statement The prepared statement.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function prepare($query) {
        return $this->getConnection()->prepare($query);
    }

    /**
     * Method used for constructing user ids IN SQL part.
     *
     * @param $userIdArray
     * @return string
     */
    public function constructUserIdsIn($userIdArray) {
        // First, make sure we only get numeric values.
        $userIds = array_filter($userIdArray, 'is_numeric');
        // Construct IN string.
        return implode( ",",$userIds);
    }
} 