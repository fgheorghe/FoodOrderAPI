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
    /**
     * Authenticate a user based on their email address and password.
     */
    public function login($username, $password) {
        $authenticated = false;

        if (!is_null($username) && !is_null($password)) {
            // TODO: Verify username and password.
        }

        return $authenticated;
    }
} 