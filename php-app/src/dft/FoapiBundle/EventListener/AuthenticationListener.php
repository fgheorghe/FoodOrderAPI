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
        // Get the current route name, and if not dft_foapi_login, check if the user is authenticated.
        $route = $this->getRouter()->getContext()->getPathInfo();

        // Get the login service.
        $loginService = $this->getContainer()->get('dft_foapi.login');

        // If user is not authenticated...
        if ($route != "/printer-callback-service/" // Printer services are using a different authentication mechanism.
            && $route != "/pending-printer-orders/"
            && $route != "/login/" && !$loginService->isAuthenticated()) {
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