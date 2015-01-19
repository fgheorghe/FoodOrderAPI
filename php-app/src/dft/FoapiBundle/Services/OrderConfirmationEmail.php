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
use dft\FoapiBundle\Services\Printer;


class OrderConfirmationEmail
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for triggering the process of sending a customer confirmation / rejection email.
     * @param $orderId
     * @param $status
     * @param $rejectionReason
     * @param $deliveryTime
     */
    public function sendAcceptanceNotification($orderId, $status, $rejectionReason, $deliveryTime)
    {
        // Get the order.
        $order = $this->getContainer()->get('dft_foapi.order')->fetchOne($orderId);

        // If the order has a customer_id attached and a set reference, begin preparing an email.
        if (!is_null($order['customer_id']) && !is_null($order['reference'])) {
            $emailFrom = $this->getEmailFromForOrder($order);
            $emailTo = $this->getEmailToForOrder($order);
            $reference = $order['reference'];

            $this->sendEmail($emailFrom, $emailTo, $reference, $status, $rejectionReason, $deliveryTime);
        }
    }

    // Method used for sending the email.
    private function sendEmail($from, $to, $reference, $status, $rejectionReason, $deliveryTime)
    {
        $mailer = $this->getContainer()->get('mailer');
        $message = $mailer->createMessage()
            ->setSubject(
                'Your food order ' . ($status == Printer::ORDER_ACCEPTED ? "is being prepared" : "has been cancelled")
            )
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->getContainer()->get('templating')->render(
                    'dftFoapiBundle:Emails:order-confirmation-email.html.twig',
                    array(
                        'reference' => $reference,
                        'rejection_reason' => !is_null($rejectionReason) ? str_replace(
                                "_",
                                " ",
                                strtolower($rejectionReason)
                            ) : '',
                        'delivery_time' => $deliveryTime
                    )
                )
            );
        $mailer->send($message);
    }

    // Method used for fetching the configured restaurant email from.
    private function getEmailFromForOrder($order)
    {
        // Prepare SQL statement for fetching the restaurant settings.
        // TODO: Figure out which is the parent id of the order user id.
        $query = "SELECT order_confirmation_from FROM restaurant_settings WHERE user_id = ?";

        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $order['user_id']);
        $statement->execute();
        $results = $statement->fetchAll();
        // And return results.
        return $results[0]['order_confirmation_from'];
    }

    // Method used for fetching the configured customer email address.
    private function getEmailToForOrder($order)
    {
        // Prepare SQL statement for fetching the customer email.
        $query = "SELECT email FROM customers WHERE id = ?";
        // Prepare statement.
        $statement = $this->prepare($query);
        $statement->bindValue(1, $order['customer_id']);
        $statement->execute();
        $results = $statement->fetchAll();
        // And return results.
        return $results[0]['email'];
    }
}