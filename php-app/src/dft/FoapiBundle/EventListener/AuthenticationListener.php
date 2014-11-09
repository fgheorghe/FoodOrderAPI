<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 09/11/14
 * Time: 00:46
 */

namespace dft\FoapiBundle\EventListener;

use dft\FoapiBundle\Traits\ContainerAware;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use dft\FoapiBundle\Other\Constants;

/**
 * Class Login
 * @package dft\FoapiBundle\Services
 */
class AuthenticationListener {
    use ContainerAware;

    // As per: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
    public function onKernelRequest(GetResponseEvent $event) {
        // Get the current route name, and if not dft_foapi_login, check if the user is authenticated.
        $routeName = $this->getContainer()->get('request')->get('_route');

        // Get the login service.
        $loginService = $this->getContainer()->get('dft_foapi.login');

        // If user is not authenticated...
        if ($routeName != "dft_foapi_login" && !$loginService->isAuthenticated()) {
            // ...get twig and display a failure message.
            $this
                ->getContainer()
                ->get('twig')
                ->loadTemplate('dftFoapiBundle:Common:failure.json.twig')
                ->render(array("reason" => Constants::LOGIN_FAILURE_CODE));

            // TODO: Implement.
        }

        // ...else continue with execution.
    }
}