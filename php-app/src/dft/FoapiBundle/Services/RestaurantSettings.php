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
 * Class RestaurantSettings.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class RestaurantSettings
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching restaurant settings.
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
                restaurant_name,
                restaurant_post_code,
                delivery_range,
                opening_time,
                closing_time,
                open_all_day,
                domain_name,
                domain_name_alias,
                default_collection_time,
                default_delivery_time,
                order_confirmation_from,
                minimum_website_order_value,
                site_contact_recipient_email,
                lunch_break,
                lunch_break_start,
                lunch_break_end,
                disable_online_payments,
                allow_unverified_pod_or_col_payment,
                payment_gateway
                FROM restaurant_settings WHERE user_id = ? LIMIT 1";
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
     * Updates restaurant settings.
     * @param $userId
     * @param $restaurantName
     * @param $restaurantPostCode
     * @param $deliveryRange
     * @param $openingTime
     * @param $closingTime
     * @param $openAllDay
     * @param $domainName
     * @param $domainNameAlias
     * @param $defaultCollectionTime
     * @param $defaultDeliveryTime
     * @param $orderConfirmationFrom
     * @param $minimumWebsiteOrderValue
     * @param $siteContactRecipientEmail
     * @param $lunchBreak
     * @param $lunchBreakStart
     * @param $lunchBreakEnd
     * @param $disableOnlinePayments
     * @param $allowUnverifiedPodOrColPayment
     * @param $paymentGateway
     */
    public function updateRestaurantSettings($userId, $restaurantName, $restaurantPostCode, $deliveryRange,
        $openingTime, $closingTime, $openAllDay, $domainName, $domainNameAlias,
        $defaultCollectionTime, $defaultDeliveryTime, $orderConfirmationFrom, $minimumWebsiteOrderValue,
        $siteContactRecipientEmail, $lunchBreak, $lunchBreakStart, $lunchBreakEnd, $disableOnlinePayments,
        $allowUnverifiedPodOrColPayment, $paymentGateway) {
        // Prepare query.
        $query = "REPLACE INTO restaurant_settings SET
            restaurant_name = ?,
            restaurant_post_code = ?,
            delivery_range = ?,
            opening_time = ?,
            closing_time = ?,
            open_all_day = ?,
            domain_name = ?,
            domain_name_alias = ?,
            user_id = ?,
            default_collection_time = ?,
            default_delivery_time = ?,
            order_confirmation_from = ?,
            minimum_website_order_value = ?,
            site_contact_recipient_email = ?,
            lunch_break = ?,
            lunch_break_start = ?,
            lunch_break_end = ?,
            disable_online_payments = ?,
            allow_unverified_pod_or_col_payment = ?,
            payment_gateway = ?";

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $restaurantName);
        $statement->bindValue(2, $restaurantPostCode);
        $statement->bindValue(3, $deliveryRange);
        $statement->bindValue(4, $openingTime);
        $statement->bindValue(5, $closingTime);
        $statement->bindValue(6, $openAllDay);
        $statement->bindValue(7, $domainName);
        $statement->bindValue(8, $domainNameAlias);
        $statement->bindValue(9, $userId);
        $statement->bindValue(10, $defaultCollectionTime);
        $statement->bindValue(11, $defaultDeliveryTime);
        $statement->bindValue(12, $orderConfirmationFrom);
        $statement->bindValue(13, $minimumWebsiteOrderValue);
        $statement->bindValue(14, $siteContactRecipientEmail);
        $statement->bindValue(15, $lunchBreak);
        $statement->bindValue(16, $lunchBreakStart);
        $statement->bindValue(17, $lunchBreakEnd);
        $statement->bindValue(18, $disableOnlinePayments);
        $statement->bindValue(19, $allowUnverifiedPodOrColPayment);
        $statement->bindValue(20, $paymentGateway);

        // Execute.
        $statement->execute();
    }
}