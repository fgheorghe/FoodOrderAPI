<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 06/12/14
 * Time: 18:45
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;

class ApiToken {
    use ContainerAware;
    use Database;
    use Logger;

    /**
     * Generate unique access tokens for the given user id.
     * Generated tokens are then saved in the database.
     * @param $userId
     */
    public function generateAndSave($userId) {
        // Generate both tokens.
        $token1 = $this->generateRandomToken($userId);
        $token2 = $this->generateRandomToken($userId);

        // Verify if these exist in the database, for other users,
        // to avoid collision (which can occur due to md5).
        while (!$this->isUnique($token1, $token2)) {
            // Generate until unique.
            $token1 = $this->generateRandomToken($userId);
            $token2 = $this->generateRandomToken($userId);
        }

        $this->persistTokens($userId, $token1, $token2);
    }

    // Helper used for persisting values in the database.
    private function persistTokens($userId, $token1, $token2) {
        $statement = $this->prepare("REPLACE INTO api_access_token SET user_id = ?, token_1 = ?, token_2 = ?");
        $statement->bindValue(1, $userId);
        $statement->bindValue(2, $token1);
        $statement->bindValue(3, $token2);
        $statement->execute();
    }

    // Helper for generating a random alphanumeric string,
    // of length 32 (as MD5) for the given user id.
    private function generateRandomToken($userId) {
        return md5(uniqid($userId, true));
    }

    // Method used for checking if a combination already exists in the database.
    private function isUnique($token1, $token2) {
        $statement = $this->prepare("SELECT * FROM api_access_token WHERE token_1 = ? AND token_2 = ? LIMIT 1");
        $statement->bindValue(1, $token1);
        $statement->bindValue(2, $token2);
        $statement->execute();
        $results = $statement->fetchAll();
        return count($results) == 0;
    }

    /**
     * Selects set tokens for a given user id.
     * If not set, an array is returned with null values.
     * @param $userId
     * @return array With the token1 and token2 keys.
     */
    public function getForUserId($userId) {
        $statement = $this->prepare("SELECT token_1, token_2 FROM api_access_token WHERE user_id = ?");
        $statement->bindValue(1, $userId);
        $statement->execute();
        $results = $statement->fetchAll();
        return (count($results) == 1) ? $results[0] : array("token_1" => null, "token_2" => null);
    }

    /**
     * Selects set tokens for a given domain name or alias.
     * If not set, an array is returned with null values.
     * @param $domainNameOrAlias
     * @return array With the token1 and token2 keys.
     */
    public function getForDomainNameOrAlias($domainNameOrAlias) {
        $statement = $this->prepare("SELECT
            token_1, token_2
            FROM api_access_token
            LEFT JOIN users ON api_access_token.user_id = users.id
            LEFT JOIN restaurant_settings ON restaurant_settings.user_id = users.id
            WHERE domain_name = ? OR domain_name_alias = ? LIMIT 1");
        $statement->bindValue(1, $domainNameOrAlias);
        $statement->bindValue(2, $domainNameOrAlias);
        $statement->execute();
        $results = $statement->fetchAll();
        return (count($results) == 1) ? $results[0] : array("token_1" => null, "token_2" => null);
    }
} 