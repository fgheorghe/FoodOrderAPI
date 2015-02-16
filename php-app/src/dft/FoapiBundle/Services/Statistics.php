<?php

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;
use Doctrine\DBAL\Connection;

/**
 * Class Statistics
 * @package dft\FoapiBundle\Services
 */
class Statistics
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Fetches monthly visitors - for months where data is available.
     *
     * @param $userId
     * @return array
     */
    public function getMonthlyVisitors($userId) {
        return $this->fetchMonthlyVisitors($userId);
    }

    private function fetchMonthlyVisitors($userId) {
        $sql = "select
                count(*) as data,
                MONTHNAME(time) as name
                from visitors
                where year(time) = year(now())
                and user_id IN (:user_id)
                group by month(time)";

        return $this->getConnection()->fetchAll(
          $sql,
          array(
              "user_id" => $userId,
          ),
          array(
            "user_id" => Connection::PARAM_INT_ARRAY
          )
        );
    }

    public function addVisitor($ipAddress, $userId) {
        $sql = "INSERT INTO visitors SET ip_address = :ip_address, user_id = :user_id, time = NOW()";

        $statement = $this->prepare($sql);
        $statement->bindValue("ip_address", $ipAddress);
        $statement->bindValue("user_id", $userId);

        $statement->execute();
    }
}