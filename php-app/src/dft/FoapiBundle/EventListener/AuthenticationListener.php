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
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Login
 * @package dft\FoapiBundle\Services
 */
class AuthenticationListener {
    use ContainerAware;

    // Hosts the router.
    private $router;

    // Sets the router.
    public function setRouter($router) {
        $this->router = $router;
        return $this;
    }

    // Fetches the router.
    public function getRouter() {
        return $this->router;
    }

    // Override the trait constructor to include the router.
    public function __construct($container, $router) {
        $this->setContainer($container);
        $this->setRouter($router);
    }

    // As per: http://symfony.com/doc/current/cookbook/event_dispatcher/before_after_filters.html
    public function onKernelRequest(GetResponseEvent $event) {
        // First, get API access tokens, if any.
        $apiAccessToken1 = $event->getRequest()->query->get('token_1', null);
        $apiAccessToken2 = $event->getRequest()->query->get('token_2', null);

        // Get the current route name, and if not dft_foapi_login, check if the user is authenticated.
        $route = $this->getRouter()->getContext()->getPathInfo();

        // Get the login service.
        $loginService = $this->getContainer()->get('dft_foapi.login');

        // First, check if we are authenticating using tokens, for any URL.
        if (!is_null($apiAccessToken1) && !is_null($apiAccessToken2)) {
            $loginService->authenticateWithTokens(
                $apiAccessToken1,
                $apiAccessToken2
            );
        // If user is not authenticated...except for login.
        // NOTE: If GET request TOKEN 1 and TOKEN 2 params are set, authenticate the user using API Access Tokens.
        }

        if ($route != "/login/"
            && substr($route, 0, 7) != "/image/"
            && substr($route, 0, 8) != "/tokens/"
            && !$loginService->isAuthenticated()) {
            // ...get twig and display a failure message.
            $response = new Response();

            $response->setContent($this
                    ->getContainer()
                    ->get('twig')
                    ->loadTemplate('dftFoapiBundle:Common:failure.json.twig')
                    ->render(array("reason" => Constants::LOGIN_FAILURE_CODE)));

            $response->setStatusCode(401);

            $event->setResponse($response);
        }

        // ...else continue with execution.
    }
}