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
 * Class EmailTemplates.
 * NOTE: This class only updates values, does not create (REPLACE INTO).
 * @package dft\FoapiBundle\Services
 */
class EmailTemplates
{
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Method used for fetching email templates.
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
                order_accepted_email_subject,
                order_accepted_email_content,
                order_rejected_email_subject,
                order_rejected_email_content
                FROM email_templates WHERE user_id = ? LIMIT 1";
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
     * Updates email templates.
     * @param $userId
     * @param $orderAcceptedEmailSubject
     * @param $orderAcceptedEmailContent
     * @param $orderRejectedEmailSubject
     * @param $orderRejectedEmailContent
     */
    public function updateEmailTemplates($userId, $orderAcceptedEmailSubject, $orderAcceptedEmailContent,
        $orderRejectedEmailSubject, $orderRejectedEmailContent) {
        // Prepare query.
        $query = "REPLACE INTO email_templates SET
            order_accepted_email_subject = ?,
            order_accepted_email_content = ?,
            order_rejected_email_subject = ?,
            order_rejected_email_content = ?,
            user_id = " . $this->constructUserIdsIn($userId);

        // Prepare statement.
        $statement = $this->prepare($query);

        // Bind parameters.
        $statement->bindValue(1, $orderAcceptedEmailSubject);
        $statement->bindValue(2, $orderAcceptedEmailContent);
        $statement->bindValue(3, $orderRejectedEmailSubject);
        $statement->bindValue(4, $orderRejectedEmailContent);

        // Execute.
        $statement->execute();
    }
}