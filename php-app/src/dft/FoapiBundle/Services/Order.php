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


class Order {
    use ContainerAware;
    use Database;
    use Logger;

    // Order type constants.
    const ORDER_TYPE_OFFLINE = 0x00;
    const ORDER_TYPE_ONLINE = 0x01;
    const ORDER_TYPE_PHONE = 0x02;
    const ORDER_TYPE_TABLE = 0x03;

    // SQL query type constants.
    const SELECT_ORDERS = 0x01;
    const COUNT_ORDERS = 0x02;
    const SELECT_ONE = 0x03;

    /**
     * Method used for fetching all orders for a given account id.
     */
    public function fetchAll($userId, $filters = array(), $orderById = "DESC") {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_ORDERS,
                    $filters,
                    $orderById
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_ORDERS,
                    $filters
            )
        );
    }

    /**
     * Method used for fetching order menu items.
     * @param $orderId
     * @return array
     */
    public function fetchOrderMenuItems($orderId) {
        // Prepare query, and execute.
        $statement = $this->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $statement->bindValue(1, $orderId);
        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) ? $results : array();
    }

    // Convenience method used for fetching order menu item ids.
    private function fetchOrderMenuItemIds($orderId) {
        // Prepare query, and execute.
        $statement = $this->prepare('SELECT menu_item_id FROM order_items WHERE order_id = ?');
        $statement->bindValue(1, $orderId);
        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) ? $results : array();
    }

    /**
     * Convenience method used for fetching order discounts as they were at the time of order creation.
     * @param $orderId
     * @return Array
     */
    public function fetchOrderFrontEndDiscounts($orderId) {
        $statement = $this->prepare("SELECT * FROM order_front_end_discounts WHERE order_id = ?");
        $statement->bindValue(1, $orderId);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * Select a single order.
     * Unlike most other fetchOne methods, the userId is optional for this one.
     * @param $orderId
     * @param $userId Integer Optional
     * @return Mixed associative array if found or null if not.
     */
    public function fetchOne($orderId, $userId = null) {
        // Prepare query.
        $query = $this->constructFetchSqlStatement(self::SELECT_ONE, $userId);

        // Prepare statement.
        $statement = $this->prepare($query);

        // Begin adding parameters.
        $i = 0;
        $statement->bindValue(++$i, $orderId);
        $statement->execute();

        $results = $statement->fetchAll();

        // Prepare for adding items, if an order was found.
        $order = null;
        if (count($results) == 1) {
            $order = $results[0];
            $order["items"] = $this->fetchOrderMenuItemIds($orderId);
        }
        // Fetch discounts as they were at the time this order was created.
        $order["front_end_discounts"] = $this->fetchOrderFrontEndDiscounts($orderId);

        return $order;
    }

    // Method used for constructing query string, without filters.
    private function constructFetchSqlStatement($queryType, $userId, $userId2 = null) {
        $query = false;
        if ($queryType == self::COUNT_ORDERS) {
            $query = "SELECT
                   count(*) as total
           FROM
               orders
           WHERE
               user_id IN (" . $this->constructUserIdsIn($userId) . ")";
        } elseif ($queryType == self::SELECT_ORDERS || $queryType == self::SELECT_ONE) {
            $query = 'SELECT
                  *,
                  (SELECT SUM(count) FROM order_items WHERE order_id = orders.id) AS item_count,
                  (SELECT name FROM users WHERE users.id = orders.user_id LIMIT 1) AS created_by,
                  (total_price - total_price * discount / 100 - front_end_discounts_total) AS final_price
                FROM
                  orders' . (!is_null($userId2) ? '
                WHERE
                  user_id IN (' . $this->constructUserIdsIn($userId2) . ')' : '');

            // Apply limit 1 if selecting a single order...and properly do it by checking if the
            // user id is set.
            if ($queryType == self::SELECT_ONE) {
                $query .= (is_null($userId2) ? ' WHERE ' : ' AND ' ) . " id = ? LIMIT 1 ";
            }
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters, $orderById = "DESC") {
        $query = $this->constructFetchSqlStatement($queryType, $userId, $userId);

        // Apply filters.
        if (array_key_exists('order_type', $filters) && !empty($filters["order_type"])) {
            if (!is_array($filters['order_type'])) {
                // Single order type filter.
                $query .= " AND order_type = ? ";
            } else {
                // Multiple order type filters.
                $query .= " AND (";
                for ($j = 0; $j < count($filters['order_type']); $j++) {
                    $query .= ($j != 0 ? ' OR ' : '') . " order_type = ? ";
                }
                $query .= ")";
            }
        }
        if (array_key_exists('reference', $filters) && !empty($filters["reference"])) {
            $query .= " AND reference LIKE ? ";
        }
        if (array_key_exists('delivery_type', $filters) && !empty($filters["delivery_type"])) {
            $query .= " AND delivery_type = ? ";
        }
        if (array_key_exists('customer_type', $filters) && !empty($filters["customer_type"])) {
            $query .= " AND customer_type = ? ";
        }
        if (array_key_exists('payment_status', $filters) && !empty($filters["payment_status"])) {
            $query .= " AND payment_status = ? ";
        }
        if (array_key_exists('customer_name', $filters) && !empty($filters["customer_name"])) {
            $query .= " AND customer_name LIKE ? ";
        }
        if (array_key_exists('customer_phone_number', $filters) && !empty($filters["customer_phone_number"])) {
            $query .= " AND customer_phone_number LIKE ? ";
        }
        if (array_key_exists('status', $filters) && is_numeric($filters["status"])) {
            $query .= " AND status = ? ";
        }
        if (array_key_exists('customer_id', $filters) && is_numeric($filters["customer_id"])) {
            $query .= " AND customer_id = ? ";
        }

        if (array_key_exists('interval', $filters) && !empty($filters["interval"])) {
            switch ($filters["interval"]) {
                case "today":
                    $query .= " AND create_date > DATE_SUB(NOW(), INTERVAL 1 DAY) ";
                    break;
                default:
                    // Do nothing.
                    break;
            }
        }

        // Apply sorting.
        if ($queryType != self::COUNT_ORDERS) {
            $query .= " ORDER BY id " . ($orderById === "DESC" ? "DESC" : "ASC");
        }

        if (array_key_exists('start', $filters) && !empty($filters["start"]) &&
            array_key_exists('limit', $filters) && !empty($filters["limit"]) &&
            $queryType != self::COUNT_ORDERS) {
            $query .= " LIMIT ?, ?";
        } elseif (array_key_exists('limit', $filters) && !empty($filters['limit'])
            && $queryType != self::COUNT_ORDERS) {
            $query .= " LIMIT ? ";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind extra parameters.
        $i = 0;
        if (array_key_exists('order_type', $filters) && !empty($filters["order_type"])) {
            if (!is_array($filters['order_type'])) {
                $statement->bindValue(++$i, $filters['order_type']);
            } else {
                // Multiple order type filters.
                for ($j = 0; $j < count($filters['order_type']); $j++) {
                    $statement->bindValue(++$i, $filters['order_type'][$j]);
                }
            }
        }
        if (array_key_exists('reference', $filters) && !empty($filters["reference"])) {
            $statement->bindValue(++$i, "%" . $filters['reference'] . "%");
        }
        if (array_key_exists('delivery_type', $filters) && !empty($filters["delivery_type"])) {
            $statement->bindValue(++$i, $filters['delivery_type']);
        }
        if (array_key_exists('customer_type', $filters) && !empty($filters["customer_type"])) {
            $statement->bindValue(++$i, $filters['customer_type']);
        }
        if (array_key_exists('payment_status', $filters) && !empty($filters["payment_status"])) {
            $statement->bindValue(++$i, $filters['payment_status']);
        }
        if (array_key_exists('customer_name', $filters) && !empty($filters["customer_name"])) {
            $statement->bindValue(++$i, "%" . $filters['customer_name'] . "%");
        }
        if (array_key_exists('customer_phone_number', $filters) && !empty($filters["customer_phone_number"])) {
            $statement->bindValue(++$i, "%" . $filters['customer_phone_number'] . "%");
        }
        if (array_key_exists('status', $filters) && is_numeric($filters["status"])) {
            $statement->bindValue(++$i, $filters['status']);
        }
        if (array_key_exists('customer_id', $filters) && is_numeric($filters["customer_id"])) {
            $statement->bindValue(++$i, $filters['customer_id']);
        }

        if (array_key_exists('start', $filters) && !empty($filters["start"]) &&
            array_key_exists('limit', $filters) && !empty($filters["limit"]) &&
            $queryType != self::COUNT_ORDERS) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        } elseif (array_key_exists('limit', $filters) && !empty($filters['limit'])
            && $queryType != self::COUNT_ORDERS) {
            $statement->bindValue(++$i, (int) $filters['limit'], \PDO::PARAM_INT);
        }

        $statement->execute();
        $results = $statement->fetchAll();

        return $queryType == self::SELECT_ORDERS ? $results : $results[0]["total"];
    }

    // Convenience method used for decoding the items array.
    // TODO: Throw exception if invalid list of items is passed in.
    private function decodeItemsArray($items) {
        return json_decode($items);
    }

    // Same as above, but for discounts.
    private function decodeFrontEndDiscountsArray($discounts) {
        return json_decode($discounts);
    }


    /**
     * Method used for creating a new order.
     * NOTE: The default order status is pending.
     * TODO: Validate input data!
     * TODO: Use transactions.
     *
     * @param $userId
     * @param $items JSON encoded array of items. Each item is an id, count and size_id object.
     * @param $deliveryAddress
     * @param $notes
     * @param $paymentStatus
     * @param $orderType
     * @param $customerType
     * @param $customerName
     * @param $customerPhoneNumber
     * @param $deliveryType
     * @param $discount
     * @param $customerId
     * @param $postCode
     * @param $reference
     * @param $userIds
     * @param $frontEndDiscounts
     */
    public function createOrder($userId, $items, $deliveryAddress, $notes, $paymentStatus, $orderType,
        $customerType, $customerName, $customerPhoneNumber, $deliveryType, $discount, $customerId, $postCode,
        $reference, $userIds, $frontEndDiscounts) {

        // First, decode items array, to be able to built the total price.
        $items = $this->decodeItemsArray($items);
        // Decode discounts array.
        $frontEndDiscounts = $this->decodeFrontEndDiscountsArray($frontEndDiscounts);

        // Prepare query.
        $query = "INSERT INTO
                orders
            SET
                user_id = ?,
                delivery_address = ?,
                notes = ?,
                status = 0,
                payment_status = ?,
                order_type = ?,
                customer_type = ?,
                customer_name = ?,
                customer_phone_number = ?,
                delivery_type = ?,
                discount = ?,
                customer_id = ?,
                post_code = ?,
                reference = ?";

        // Prepare statement and bind parameters.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $deliveryAddress);
        $statement->bindValue(3, $notes);
        $statement->bindValue(4, $paymentStatus); // TODO: Add constants.
        $statement->bindValue(5, $orderType);
        $statement->bindValue(6, $customerType);
        $statement->bindValue(7, $customerName);
        $statement->bindValue(8, $customerPhoneNumber);
        $statement->bindValue(9, $deliveryType);
        $statement->bindValue(10, $discount);
        $statement->bindValue(11, $customerId ? $customerId : null);
        $statement->bindValue(12, $postCode);
        $statement->bindValue(13, $reference);

        // Execute query.
        $statement->execute();

        // Get last insert id.
        // TODO: Verify for errors in previous statement.
        $orderId = $this->getConnection()->lastInsertId();

        // Store items.
        $totalPrice = $this->storeOrderItemsAndGetTotalPrice($orderId, $userId, $items, $userIds);

        // Store discounts and get final price.
        $frontEndDiscountsTotal = $this->storeFrontEndDiscountsAndGetFinalPrice(
            $orderId,
            $items,
            $frontEndDiscounts,
            $totalPrice,
            $userIds
        );

        // Finally, store total price.
        $this->storeOrderTotalPrice($orderId, $totalPrice, $frontEndDiscountsTotal);

	// Notify the restaurant owner, about this order.
	if ($orderType == self::ORDER_TYPE_ONLINE) {
		$this->sendSMSNotification(array(
			"customer_name" => $customerName,
			"reference" => $reference,
			"post_code" => $postCode,
			"id" => $orderId,
			"user_id" => $userId,
			"customer_phone_number" => $customerPhoneNumber,
			"final_price" => $totalPrice - $frontEndDiscountsTotal
		));
	}
    }

    // TODO: Document and add logging.
    private function sendSMSNotification($order) {
	// First, get settings.
	$smsNotificationSettings = $this->getContainer()->get('dft_foapi.sms_notification_settings')->fetchOne($order["user_id"]);
	// Then notify.
	if ($smsNotificationSettings["enable_sms_notifications_on_online_orders"] == 1 &&
	    !empty($smsNotificationSettings["sms_order_notification_recipient"])) {
		 $smsGatewayService = $this->getContainer()->get('dft_foapi.sms_gateway');
		 $smsGatewayService->setSMSUsername($smsNotificationSettings["sms_gateway_username"]);
		$smsGatewayService->setSMSGatewayPassword($smsNotificationSettings["sms_gateway_password"]);
		 $smsGatewayService->sendOrderNotificationSms(
			$smsNotificationSettings["sms_order_notification_recipient"],
			$order
		 );
	}
    }

    // Convenience method used for storing an item against the order.
    private function storeOrderItem($orderId, $itemRow, $item) {
        // Prepare query.
        $query = "INSERT INTO
                order_items
            SET
                order_id = ?,
                item_name = ?,
                category_name = (SELECT
                        category_name
                    FROM
                        menu_item_categories
                    LEFT JOIN
                        menu_items
                    ON
                        menu_item_categories.id = menu_items.category_id
                    WHERE
                        menu_items.id = ?
                    LIMIT 1
                ),
                price = ?,
                size_name = (SELECT size_name FROM menu_item_sizes WHERE id = ?),
                count = ?,
                menu_item_id = ?";

        // Bind parameters.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);
        $statement->bindValue(2, $itemRow["item_name"]);
        $statement->bindValue(3, $itemRow["id"]);
        $statement->bindValue(4, $itemRow["price"]);
        $statement->bindValue(5, $item->size_id);
        $statement->bindValue(6, $item->count);
        $statement->bindValue(7, $itemRow["id"]);

        // ...execute.
        $statement->execute();
    }

    // Convenience method used for storing the total price against an order.
    private function storeOrderTotalPrice($orderId, $totalPrice, $frondEndDiscountsTotal) {
        // Prepare query.
        $query = "UPDATE orders SET total_price = ?, front_end_discounts_total = ? WHERE id = ? LIMIT 1";

        // Prepare statement and execute.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $totalPrice);
        $statement->bindValue(2, $frondEndDiscountsTotal);
        $statement->bindValue(3, $orderId);
        $statement->execute();
    }

    // Same as storeOrderItemsAndGetTotalPrice, but for discounts; and returns the total of front end discounts.
    private function storeFrontEndDiscountsAndGetFinalPrice($orderId, $items, $discounts, $totalPrice, $userIds) {
        $frontEndDiscountsTotal = 0;

        $frontEndDiscountsService = $this->getContainer()->get('dft_foapi.front_end_discounts');
        $menuItemService = $this->getContainer()->get('dft_foapi.menu_item');

        $insertQuery = "INSERT INTO
              order_front_end_discounts
            SET
              order_id = ?,
              discount_type = ?,
              discount_value = ?,
              discount_name = ?,
              discount_item_id = ?,
              discount_item_name = ?,
              discount_id = ?";

        // Fetch menu items part of this order.
        $itemsArray = array();
        foreach ($items as $item) {
            $itemRow = $menuItemService->fetchOne($item->id, $userIds);
            $itemsArray[$item->id] = $itemRow;
        }

        // Get each discounts and copy it to the discounts table.
        foreach ($discounts as $discountId) {
            $discountRow = $frontEndDiscountsService->fetchOne($discountId, $userIds);
            if (is_null($discountRow)) {
                continue;
            }

            $applyDiscount = false;
            // First, determine if this discount can be applied.
            if ($discountRow["discount_type"] == 1
                && array_key_exists($discountRow["discount_item_id"], $itemsArray)
                && $totalPrice > $discountRow["value"] ) {
                $applyDiscount = true;
                $frontEndDiscountsTotal += $itemsArray[$discountRow["discount_item_id"]]["price"];
            }
            if ($discountRow["discount_type"] == 0) {
                $applyDiscount = true;
                $frontEndDiscountsTotal += number_format(($totalPrice * $discountRow["value"]/100), 2);
            }
            if ($applyDiscount) {
                $statement = $this->prepare($insertQuery);
                $statement->bindValue(1, $orderId);
                $statement->bindValue(2, $discountRow["discount_type"]);
                $statement->bindValue(3, $discountRow["value"]);
                $statement->bindValue(4, $discountRow["discount_name"]);
                $statement->bindValue(5, $discountRow["discount_item_id"]);
                $statement->bindValue(6, $discountRow["discount_item_name"]);
                $statement->bindValue(7, $discountRow["id"]);
                $statement->execute();
            }
        }

        return $frontEndDiscountsTotal;
    }

    // Convenience method used for storing order items, and returning the order total, WITHOUT discount applied,
    // for items belonging to a given user.
    private function storeOrderItemsAndGetTotalPrice($orderId, $userId, $itemsArray, $userIds) {
        $totalPrice = 0;
        // Get the item service.
        $menuItemService = $this->getContainer()->get('dft_foapi.menu_item');

        // First, check if the order item belongs to this user id, by fetching it.
        foreach ($itemsArray as $item) {
            $itemRow = $menuItemService->fetchOne($item->id, $userIds);
            if (!is_null($itemRow)) {
                // Add to price total.
                $totalPrice += $item->count * $itemRow['price'];

                // ...and store the item for this order.
                // TODO: Check for errors.
                $this->storeOrderItem($orderId, $itemRow, $item);
            }
        }

        return $totalPrice;
    }

    /**
     * Method used for deleting an order - primarily used when updating orders.
     *
     * @param $orderId
     * @param $userId
     */
    public function deleteOrder($orderId, $userId) {
        // Prepare and execute.
        $query = "DELETE FROM orders WHERE id = ? AND user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();

        if ($statement->rowCount() == 1) {
            $this->deleteOrderItems($orderId);
            $this->deleteOrderFrontEndDiscounts($orderId);
        }
    }

    // Same as deleteOrderItems.
    private function deleteOrderFrontEndDiscounts($orderId) {
        // Prepare and execute.
        $query = "DELETE FROM order_front_end_discounts WHERE order_id = ?";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();
    }

    // Method used for deleting order items.
    // NOTE: Do not expose, so that order items are only deleted if items belong to a given user...
    // ...so if the number of affected rows after deleting orders is == 1.
    private function deleteOrderItems($orderId) {
        // Prepare and execute.
        $query = "DELETE FROM order_items WHERE order_id = ?";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();
    }

    /**
     * Method used for deleting an order. This essentially removes the old order, and creates a new one.
     *
     * @param $userIds
     * @param $userId
     * @param $orderId
     * @param $items
     * @param $deliveryAddress
     * @param $notes
     * @param $paymentStatus
     * @param $orderType
     * @param $customerType
     * @param $customerName
     * @param $customerPhoneNumber
     * @param $deliveryType
     * @param $discount
     * @param $customerId
     * @param $postCode
     * @param $reference
     * @param $frontEndDiscounts
     */
    public function updateOrder($userIds, $userId, $orderId, $items, $deliveryAddress, $notes, $paymentStatus, $orderType,
        $customerType, $customerName, $customerPhoneNumber, $deliveryType, $discount, $customerId, $postCode, $reference,
        $frontEndDiscounts) {
        // Delete order.
        $this->deleteOrder($orderId, $userIds);

        // Create the new order.
        $this->createOrder($userId, $items, $deliveryAddress, $notes, $paymentStatus, $orderType,
            $customerType, $customerName, $customerPhoneNumber, $deliveryType, $discount, $customerId, $postCode,
            $reference, $userIds, $frontEndDiscounts);
    }

    /**
     * Method used for setting an order as printed
     * @param $userId
     * @param $orderId
     */
    public function printedOrder($userId, $orderId) {
        // Prepare and execute.
        $query = "UPDATE orders SET status = 1 WHERE id = ? AND user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();
    }

    /**
     * Method used for canceling an order.
     * @param $userId
     * @param $orderId
     */
    public function cancelOrder($userId, $orderId) {
        // Prepare and execute.
        $query = "UPDATE orders SET status = 3 WHERE id = ? AND user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();
    }

    /**
     * Method used for accepting an order.
     * @param $userId
     * @param $deliveryTime
     * @param $orderId
     */
    public function acceptOrder($userId, $deliveryTime, $orderId) {
        // Prepare and execute.
        $query = "UPDATE orders
            SET
              status = 2,
              printer_message = 'WEB_ACCEPTED',
              delivery_time = DATE_ADD(NOW(), INTERVAL ? MINUTE)
            WHERE
              id = ?
              AND user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, (int) $deliveryTime);
        $statement->bindValue(2, $orderId);

        $statement->execute();

        // Notify customer.
        $this->getContainer()
            ->get('dft_foapi.order_confirmation_email')
            ->sendAcceptanceNotification(
                $userId,
                $orderId,
                2,
                "",
                $deliveryTime
            );
    }

    /**
     * Method used for rejecting an order.
     * @param $userId
     * @param $orderId
     */
    public function rejectOrder($userId, $orderId) {
        // Prepare and execute.
        $query = "UPDATE orders
            SET
              status = 3,
              printer_message = 'WEB_REJECTED'
            WHERE
              id = ?
              AND user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();

        // Notify customer.
        $this->getContainer()
            ->get('dft_foapi.order_confirmation_email')
            ->sendAcceptanceNotification(
                $userId,
                $orderId,
                3,
                "",
                ""
            );
    }

    /**
     * Method used for 'reprinting' an order. Setting the status to pending that is.
     * @param $userId
     * @param $orderId
     */
    public function reprintOrder($userId, $orderId) {
        // Prepare and execute.
        $query = "UPDATE orders SET status = 0 WHERE id = ? AND user_id IN (" . $this->constructUserIdsIn($userId) . ") LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);

        $statement->execute();
    }
}
