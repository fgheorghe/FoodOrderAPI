<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 18/11/14
 * Time: 19:27
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;


/**
 * Class SMSNotificationSettings.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class SMSNotificationSettings
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching barclays payment settings.
     * NOTE: Returns the domain_name restaurant settings value, for a client to construct the return url.
     * @param $userId
     * @return null
     */
    public function fetchOne($userId)
    {
        return $this->executeFetchOneStatement($userId);
    }

    // Convenience method used for constructing the select query.
    private function constructFetchOneSqlStatement()
    {
        return "SELECT
                sms_gateway_username,
                sms_gateway_password,
                enable_sms_notifications_on_online_orders
                FROM sms_notification_settings
                WHERE sms_notification_settings.user_id = ? LIMIT 1";
    }

    // Method used for executing query.
    private function executeFetchOneStatement($userId)
    {
        $query = $this->constructFetchOneSqlStatement();

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);

        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) == 1 ? $results[0] : null;
    }

    /**
     * Updates barclays payment settings.
     * @param $userId
     * @param $username
     * @param $password
     * @param $enable
     */
    public function updateSMSNotificationSettings($userId, $username, $password, $enable) {
        // Prepare query.
        $query = "REPLACE INTO sms_notification_settings SET
            sms_gateway_username = ?,
            sms_gateway_password = ?,
            enable_sms_notifications_on_online_orders = ?,
            user_id = ?";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $username);
        $statement->bindValue(2, $password);
        $statement->bindValue(3, $enable);
        $statement->bindValue(4, $userId);

        // Execute.
        $statement->execute();
    }
}
