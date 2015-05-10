<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 09/11/14
 * Time: 00:46
 */

namespace dft\FoapiBundle\Services;

use dft\FoapiBundle\Traits\ContainerAware;
use dft\FoapiBundle\Traits\Database;
use dft\FoapiBundle\Traits\Logger;


/**
 * Class Login
 * @package dft\FoapiBundle\Services
 */
class Login {
    use ContainerAware;
    use Database;
    use Logger;

    // Fetch user by...
    const GET_USER_BY_EMAIL = 0x01;
    const GET_USER_BY_ID = 0x02;

    // Convenience method used for encrypting a password, using the standard PHP crypt function.
    public function encryptPassword($password) {
        return @crypt($password);
    }

    /**
     * Check user login by given field (email or id) and password.
     * @throws Exception if an invalid $by value is used.
     * @param $by
     * @param $value
     * @param $password
     * @return bool
     */
    public function checkUserLoginBy($by, $value, $password) {
        $authenticated = false;

        switch ($by) {
            case self::GET_USER_BY_EMAIL:
                $column = 'email';
                break;
            case self::GET_USER_BY_ID:
                $column = 'id';
                break;
            default:
                throw new Exception("Invalid by value.");
                break;
        }

        // If credentials are passed in, authenticate the user.
        if (!is_null($value) && !is_null($password)) {
            // Get doctrine, and query the database.
            $statement = $this->prepare("SELECT
                          id,
                          password
                        FROM
                          users
                        WHERE
                          " . $column . " = ?
                        LIMIT 1");

            $statement->bindValue(1, $value);
            $statement->execute();
            $user = $statement->fetchAll();

            // Verify password.
            $authenticated = count($user) ? $this->compareEncryptedPasswords($user[0]['password'], $password) : false;

            // Store in session if authentication succeeded.
            if ($authenticated) {
                $this->storeUserIdInSession($user[0]['id']);
            }
        }

        return $authenticated;
    }

    /**
     * Authenticate a user based on their email address and password.
     */
    public function login($username, $password) {
            return $this->checkUserLoginBy(self::GET_USER_BY_EMAIL, $username, $password);
    }

    // Method used for storing the user id in the session.
    private function storeUserIdInSession($userId) {
        $this
            ->getContainer()
            ->get('session')
            ->set("userId", $userId);
    }

    // Method used for comparing the DB password with the user input password,
    // as per: http://php.net/manual/en/function.crypt.php
    public function compareEncryptedPasswords($hashedPassword, $userInput) {
        return $hashedPassword == @crypt($userInput, $hashedPassword);
    }

    /**
     * Method used for checking if a user is already authentiated.
     */
    public function isAuthenticated() {
        return $this->getAuthenticatedUserId() ? true : false;
    }

    /**
     * Method used for fetching the currently authenticated user id.
     */
    public function getAuthenticatedUserId() {
        return $this
            ->getContainer()
            ->get('session')
            ->get("userId", false);
    }

    /**
     * Method used for logging the user out.
     */
    public function doLogout() {
        $this
            ->getContainer()
            ->get('session')
            ->invalidate();
    }

    /**
     * Method used for authenticating with access tokens.
     * NOTE: Invalidates a session on failed login attempts using tokens!
     */
    public function authenticateWithTokens($token1, $token2) {
        $statement = $this->prepare("SELECT user_id FROM api_access_token WHERE token_1 = ? AND token_2 = ? LIMIT 1");
        $statement->bindValue(1, $token1);
        $statement->bindValue(2, $token2);
        $statement->execute();
        $user = $statement->fetchAll();
        if (count($user) == 1) {
            $this->storeUserIdInSession($user[0]["user_id"]);
        } else {
            $this->doLogout();
        }
    }
} 