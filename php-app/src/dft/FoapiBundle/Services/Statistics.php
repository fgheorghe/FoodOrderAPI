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
        return $this->packMonths($this->fetchMonthlyVisitors($userId));
    }

    /**
     * Get monthly order values.
     *
     * @param $userId
     * @return array
     */
    public function getMonthlyOrderValues($userId) {
        return $this->packMonths($this->fetchMonthlyOrderValues($userId));
    }

    /**
     * Top 3 monthly selling products.
     *
     * @param $userId
     * @return array
     */
    public function getMonthlySellingProducts($userId) {
        return $this->fetchMonthlySellingProducts($userId);
    }

    /**
     * Top 3 post codes.
     *
     * @param $userId
     * @return array
     */
    public function getMonthlyPostcodes($userId) {
        return $this->fetchMonthlyPostcodes($userId);
    }

    // Convenience method used for adding 'missing' months.
    public function packMonths($monthlyVisitors) {
        $packedArray = array();
        $availableMonths = array();
        $availableMonthData = array();

        // First, figure out which months are there.
        foreach ($monthlyVisitors as $monthData) {
            $availableMonths[] = $monthData["name"];
            $availableMonthData[$monthData["name"]] = $monthData;
        }

        // Then parse each month...
        foreach (array("January", "February", "March", "April", "May", "June", "July", "August", "September",
                     "October", "November", "December") as $month) {
            // For non existent months, add a count of 0.
            if (!in_array($month, $availableMonths)) {
                $packedArray[] = array(
                    "name" => $month,
                    "data" => 0
                );
            } else {
                // For existent months, push data to array.
                $packedArray[] = $availableMonthData[$month];
            }
        }

        return $packedArray;
    }

    // Method used for fetching top 3 monthly selling products.
    private function fetchMonthlyPostcodes($userId) {
        $sql = "select
                SUBSTRING(post_code, 1, 3) as name,
                count(*) as data
                from order_items
                join orders
                on orders.id = order_items.order_id
                where user_id in (:user_id)
                and post_code is not null
                group by  SUBSTRING(post_code, 1, 3)
                order by data desc limit 3";

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

    // Method used for fetching top 3 monthly selling products.
    private function fetchMonthlySellingProducts($userId) {
        $sql = "select
                item_name as name,
                count(*) as data
                from order_items
                join orders
                on orders.id = order_items.order_id
                where user_id in (:user_id)
                group by  menu_item_id
                order by data desc limit 3";

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

    // Method used for fetching statistics, for existing months.
    private function fetchMonthlyOrderValues($userId) {
        $sql = "select
                SUM(total_price - total_price * discount / 100 - front_end_discounts_total) as data,
                MONTHNAME(create_date) as name
                from orders
                where year(create_date) = year(now())
                and user_id IN (:user_id)
                group by month(create_date)";

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

    // Method used for fetching statistics, for existing months.
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

    /**
     * Method used for 'recording' a visitor.
     *
     * @param $ipAddress
     * @param $userId
     */
    public function addVisitor($ipAddress, $userId) {
        $sql = "INSERT INTO visitors SET ip_address = :ip_address, user_id = :user_id, time = NOW()";

        $statement = $this->prepare($sql);
        $statement->bindValue("ip_address", $ipAddress);
        $statement->bindValue("user_id", $userId);

        $statement->execute();
    }
}