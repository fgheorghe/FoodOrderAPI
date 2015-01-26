<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 30/11/14
 * Time: 12:49
 */

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class BaseController. Provides common controller functionality.
 * @package dft\FoapiBundle\Controller
 */
class BaseController extends Controller {
    // Wrapper for the same user in the User service.
    protected function getAuthenticatedUserIdAndSubAccountIds($userId = null) {
        return $this->container->get('dft_foapi.user')->getAuthenticatedUserIdAndSubAccountIds($userId);
    }
} 