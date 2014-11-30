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
    /**
     * Method used for fetching the currently logged in user id, and sub account ids as an array.
     * @return mixed
     */
    protected function getAuthenticatedUserIdAndSubAccountIds() {
        // Get the logged in user id.
        $userId = $this->container->get('dft_foapi.login')->getAuthenticatedUserId();
        // Get sub account ids.
        $subAccountIds = $this->container->get('dft_foapi.user')->getSubAccountIds($userId);

        // Add the logged in user id and return.
        $subAccountIds[] = $userId;
        return $subAccountIds;
    }
} 