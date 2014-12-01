<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 30/11/14
 * Time: 16:03
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;

class Printer
{
    use ContainerAware;
    use Database;
    use Logger;

    // Logs printer activity.
    // NOTE: Makes use of the client IP address.
    private function logPrinterActivity($printerAccountId, $serviceName, $data)
    {
        // TODO: Add user id.
        $query = 'INSERT INTO
                activity_log_entries
            SET
                user_id = ?,
                date_time = NOW(),
                ip_address = ?,
                requested_service_name = ?,
                request_data = ?';
        $statement = $this->prepare($query);
        $statement->bindValue(1, $printerAccountId);
        $statement->bindValue(2, $this->getContainer()->get('request')->getClientIp());
        $statement->bindValue(3, $serviceName);
        $statement->bindValue(4, $data);
        $statement->execute();
    }

    // Convenience method used for authenticating a printer, by username and password
    // for an account id. The account id is the parent user id.
    private function authenticatePrinter($username, $password, $accountId)
    {
        // TODO: Implement.
        return true;
    }

    /**
     * Method used for updating an order, as per printer input.
     * TODO: Add parameter validation.
     * @param $username
     * @param $password
     * @param $orderId
     * @param $accountId
     * @param $printerMessage
     * @param $deliveryTime
     * @param $acceptedOrRejected
     */
    public function updateOrder(
        $username,
        $password,
        $orderId,
        $accountId,
        $printerMessage,
        $deliveryTime,
        $acceptedOrRejected
    ) {
        if ($this->authenticatePrinter($username, $password, $accountId)) {
            // Prepare order status.
            $orderStatus = $acceptedOrRejected ?
                (strtolower($acceptedOrRejected) === "rejected" ?
                    3 :
                    (strtolower($acceptedOrRejected) === "accepted" ? 2 : 99))
                : 99;

            // Prepare rejection reason.
            $rejectionReason = $printerMessage ? $printerMessage : "";

            // Set status to ERROR if an invalid $deliveryTime is set.
            // NOTE: TODO: For now, allow for HH:MM. In future, allow only for minutes! Pending printer changes.
            $tempDeliveryTime = explode(":", $deliveryTime);
            if (count($tempDeliveryTime) != 2) {
                $orderStatus = 99;
                $rejectionReason = "Invalid delivery time: " . $deliveryTime;
                $deliveryTime = 0;
            } else {
                $deliveryTime = (int) $tempDeliveryTime[0] * 60 + (int) $tempDeliveryTime[1];
            }

            // NOTE: Delivery time, which will be added to NOW() as minutes.
            // Prepare query and statement.
            // TODO: Verify owner id.
            $query = "UPDATE orders SET status = ?, delivery_time = DATE_ADD(NOW(), INTERVAL ? MINUTE), printer_message = ? WHERE id = ? LIMIT 1";
            $statement = $this->prepare($query);
            $statement->bindValue(1, $orderStatus);
            $statement->bindValue(2, $deliveryTime);
            $statement->bindValue(3, $rejectionReason);
            $statement->bindValue(4, $orderId);
            $statement->execute();

            // Log action.
            $this->logPrinterActivity(
                1,
                "printer-callback-service",
                "Status: $orderStatus Delivery time: $deliveryTime Rejection reason: $rejectionReason Order id: $orderId"
            );
        }
    }

    /**
     * Method used for fetching pending printer orders - one by one! It constructs a printer specific text message, with this format:
     * #RestaurantId*OrderType*Order ID*CategoryOrSpace|Quantity|Food1|Price;CategoryOrSpace|Quantity|Food2|Price;CategoryOrSpace|Quantity|Food3|Price*Discount;Total;CustomerType;CustomerName;CustomerAddress;RequestedTime;NumberOfPrevOrder;PaymentStatus;CustomerTelephone*CusstomerComments#
     *
     * Order types are: Delivery-1, Collection-2
     * Customer types are: Verified-4, Not Verified-5
     * Payment status: Order Paid-6, Order not paid-7
     * Date: 15:47 2012-11-12
     *
     * @param $username
     * @param $password
     * @param $accountId
     * @return string
     */
    public function pendingOrders($username, $password, $accountId)
    {
        if ($this->authenticatePrinter($username, $password, $accountId)) {
            // Get the order service.
            $orderService = $this->getContainer()->get('dft_foapi.order');

            // Fetch one by one.
            $orders = $orderService->fetchAll(
                array(1), // TODO: Add user id, and limit to Online (1), Phone (2), Table (3) order types.
                array(
                    "status" => 0,
                    "limit" => 1
                ),
                "ASC"
            );
            // Check if there are any orders.
            if (count($orders["data"]) == 0) {
                return "";
            }
            $order = $orders["data"][0];

            // Construct response.
            $response = $this->orderItemsToPrinterText(
                $orderService->fetchOrderMenuItems($order["id"])
            );

            // Construct order part.
            $response = $this->formatOrderResponseString($order, $this->formatOrderPart($order, $response));

            // Set status as sent to printer. TODO: Match against user id, and move to orders service.
            $query = 'UPDATE orders SET status = 1 WHERE id = ?';
            $statement = $this->prepare($query);
            $statement->bindValue(1, $order["id"]);
            $statement->execute();

            // Log action.
            $this->logPrinterActivity(
                1,
                "pending-printer-orders",
                ""
            );

            return $response;
        } else {
            return "Invalid credentials.";
        }
    }

    // Method used for converting an order array to printer text.
    private function orderItemsToPrinterText($orderItems)
    {
        $response = "";
        foreach ($orderItems as $orderItem) {
            $response .= ($response !== "" ? ";" : "") . $this->formatOrderItemText($orderItem);
        }
        return $response;
    }

    // Method used for converting an order item to printer text.
    private function formatOrderItemText($orderItemsArray)
    {
        return $orderItemsArray['category_name'] . "|" . $orderItemsArray['count'] . "|" . $orderItemsArray['item_name'] . "|" . $orderItemsArray['price'];
    }

    // Method used for formatting the order part.
    private function formatOrderPart($order, $orderItemsText)
    {
        return $orderItemsText . "*0.0;" . $order['total_price'] . ";" . $order['customer_type'] . ";" . $order['customer_name'] . ";" . $order['delivery_address'] . ";" . $order['create_date'] . ";0;" . $order['payment_status'] . ";" . $order['customer_phone_number'] . "*" . $order['notes'];
    }

    // Convenience metohd used for formatting the full order response text.
    private function formatOrderResponseString($order, $orderAndItemsText)
    {
        // TODO: Add restaurant id
        return "#1*" . $order['delivery_type'] . "*" . $order['id'] . "*" . $orderAndItemsText . "#";
    }
} 