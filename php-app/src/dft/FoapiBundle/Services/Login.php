<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 09/11/14
 * Time: 00:46
 */

namespace dft\FoapiBundle\Services;

/**
 * Class Login
 * @package dft\FoapiBundle\Services
 */
class Login {
    // Hosts the service container.
    private $container;

    /**
     * Sets the service container object.
     * @return mixed
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * Gets the service container object.
     * @param $container
     * @return $this
     */
    public function setContainer($container) {
        $this->container = $container;
        return $this;
    }

    // Takes in the container.
    public function __construct($container) {
        $this->setContainer($container);
    }

    // Convenience method used for encrypting a password, using the standard PHP crypt function.
    public function encryptPassword($password) {
        return crypt($password);
    }

    /**
     * Authenticate a user based on their email address and password.
     */
    public function login($username, $password) {
        $authenticated = false;

        // If credentials are passed in, authenticate the user.
        if (!is_null($username) && !is_null($password)) {
            // Get doctrine, and query the database.
            $statement = $this
                ->getContainer()
                ->get('doctrine')
                ->getEntityManager()
                ->getConnection()
                ->prepare("SELECT
                      id,
                      password
                    FROM
                      users
                    WHERE
                      email = ?
                    LIMIT 1");

            $statement->bindValue(1, $username);
            $statement->execute();
            $user = $statement->fetchAll();

            // Verify password.
            $authenticated = $this->compareEncryptedPasswords($user[0]['password'], $password);

            // Store in session if authentication succeeded.
            if ($authenticated) {
                $this->storeUserIdInSession($user[0]['id']);
            }
        }

        return $authenticated;
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
    private function compareEncryptedPasswords($hashedPassword, $userInput) {
        return $hashedPassword == crypt($userInput, $hashedPassword);
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
} 