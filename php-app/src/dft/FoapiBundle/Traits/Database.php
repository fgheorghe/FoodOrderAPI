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
} 