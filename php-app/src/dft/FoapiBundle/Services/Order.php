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

    // SQL query type constants.
    const SELECT_ORDERS = 0x01;
    const COUNT_ORDERS = 0x02;
    const SELECT_ONE = 0x03;

    /**
     * Method used for fetching all orders for a given account id.
     */
    public function fetchAll($userId, $filters = array()) {
        return array(
            "data" => $this->executeFetchAllStatement(
                    $userId,
                    self::SELECT_ORDERS,
                    $filters
            ),
            "total" => $this->executeFetchAllStatement(
                    $userId,
                    self::COUNT_ORDERS,
                    $filters
            )
        );
    }

    // Convenience method used for fetching order items.
    private function fetchOrderMenuItemIds($orderId) {
        // Prepare query, and execute.
        $statement = $this->prepare('SELECT menu_item_id FROM order_items WHERE order_id = ?');
        $statement->bindValue(1, $orderId);
        $statement->execute();
        $results = $statement->fetchAll();

        return count($results) ? $results : array();
    }

    /**
     * Select a single order.
     * @param $userId
     * @param $orderId
     * @return Mixed associative array if found or null if not.
     */
    public function fetchOne($userId, $orderId) {
        // Prepare query.
        $query = $this->constructFetchSqlStatement(self::SELECT_ONE);

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->bindValue(1, $this->constructUserIdsIn($userId));
        $statement->bindValue(2, $orderId);
        $statement->execute();

        $results = $statement->fetchAll();

        // Prepare for adding items, if an order was found.
        $order = null;
        if (count($results) == 1) {
            $order = $results[0];
            $order["items"] = $this->fetchOrderMenuItemIds($orderId);
        }

        return $order;
    }

    // Method used for constructing query string, without filters.
    private function constructFetchSqlStatement($queryType) {
        $query = false;
        if ($queryType == self::COUNT_ORDERS) {
            $query = "SELECT
                   count(*) as total
           FROM
               orders
           WHERE
               user_id IN (?)";
        } elseif ($queryType == self::SELECT_ORDERS || $queryType == self::SELECT_ONE) {
            $query = 'SELECT
                  *,
                  (SELECT name FROM users WHERE users.id = orders.user_id LIMIT 1) AS created_by,
                  (total_price - total_price * discount / 100) AS final_price
                FROM
                  orders
                WHERE
                  user_id IN (?)';

            // Apply limit 1 if selecting a single order.
            if ($queryType == self::SELECT_ONE) {
                $query .= " AND id = ? LIMIT 1 ";
            }
        }

        return $query;
    }

    // Method used for executing query, and applying filters.
    private function executeFetchAllStatement($userId, $queryType, $filters) {
        $query = $this->constructFetchSqlStatement($queryType);

        // Apply filters.
        if (array_key_exists('order_type', $filters) && !empty($filters["order_type"])) {
            $query .= " AND order_type = ? ";
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
        $query .= " ORDER BY id DESC ";

        if (array_key_exists('start', $filters) && !empty($filters["start"]) &&
            array_key_exists('limit', $filters) && !empty($filters["limit"]) &&
            $queryType != self::COUNT_ORDERS) {
            $query .= " LIMIT ?, ?";
        }

        // Prepare statement.
        $statement = $this->prepare($query);

        $statement->bindValue(1, $this->constructUserIdsIn($userId));

        // Bind extra parameters.
        $i = 1;
        if (array_key_exists('order_type', $filters) && !empty($filters["order_type"])) {
            $statement->bindValue(++$i, $filters['order_type']);
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

        if (array_key_exists('start', $filters) && !empty($filters["start"]) &&
            array_key_exists('limit', $filters) && !empty($filters["limit"]) &&
            $queryType != self::COUNT_ORDERS) {
            $statement->bindValue(++$i, (int) $filters['start'], \PDO::PARAM_INT);
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
     */
    public function createOrder($userId, $items, $deliveryAddress, $notes, $paymentStatus, $orderType,
        $customerType, $customerName, $customerPhoneNumber, $deliveryType, $discount, $customerId, $postCode) {

        // First, decode items array, to be able to built the total price.
        $items = $this->decodeItemsArray($items);

        // Prepare query.
        $query = "INSERT INTO
                orders
            SET
                user_id = ?,
                delivery_address = ?,
                notes = ?,
                status = 'pending',
                payment_status = ?,
                order_type = ?,
                customer_type = ?,
                customer_name = ?,
                customer_phone_number = ?,
                delivery_type = ?,
                discount = ?,
                customer_id = ?,
                post_code = ?";

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

        // Execute query.
        $statement->execute();

        // Get last insert id.
        // TODO: Verify for errors in previous statement.
        $orderId = $this->getConnection()->lastInsertId();

        // Store items.
        $totalPrice = $this->storeOrderItemsAndGetTotalPrice($orderId, $userId, $items);

        // Finally, store total price.
        $this->storeOrderTotalPrice($orderId, $totalPrice);
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
    private function storeOrderTotalPrice($orderId, $totalPrice) {
        // Prepare query.
        $query = "UPDATE orders SET total_price = ? WHERE id = ? LIMIT 1";

        // Prepare statement and execute.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $totalPrice);
        $statement->bindValue(2, $orderId);
        $statement->execute();
    }

    // Convenience method used for storing order items, and returning the order total, WITHOUT discount applied,
    // for items belonging to a given user.
    private function storeOrderItemsAndGetTotalPrice($orderId, $userId, $itemsArray) {
        $totalPrice = 0;
        // Get the item service.
        $menuItemService = $this->getContainer()->get('dft_foapi.menu_item');

        // First, check if the order item belongs to this user id, by fetching it.
        foreach ($itemsArray as $item) {
            $itemRow = $menuItemService->fetchOne($item->id, $userId);
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
        $query = "DELETE FROM orders WHERE id = ? AND user_id IN (?) LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);
        $statement->bindValue(2, $this->constructUserIdsIn($userId));

        $statement->execute();

        if ($statement->rowCount() == 1) {
            $this->deleteOrderItems($orderId);
        }
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
     */
    public function updateOrder($userIds, $userId, $orderId, $items, $deliveryAddress, $notes, $paymentStatus, $orderType,
        $customerType, $customerName, $customerPhoneNumber, $deliveryType, $discount, $customerId, $postCode) {
        // Delete order.
        $this->deleteOrder($orderId, $userIds);

        // Create the new order.
        $this->createOrder($userId, $items, $deliveryAddress, $notes, $paymentStatus, $orderType,
            $customerType, $customerName, $customerPhoneNumber, $deliveryType, $discount, $customerId, $postCode);
    }

    /**
     * Method used for canceling an order.
     * @param $userId
     * @param $orderId
     */
    public function cancelOrder($userId, $orderId) {
        // Prepare and execute.
        $query = "UPDATE orders SET status = 3 WHERE id = ? AND user_id IN (?) LIMIT 1";

        $statement = $this->prepare($query);
        $statement->bindValue(1, $orderId);
        $statement->bindValue(2, $this->constructUserIdsIn($userId));

        $statement->execute();
    }
}