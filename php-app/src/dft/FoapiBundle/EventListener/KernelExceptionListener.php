<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 22/11/14
 * Time: 11:00
 */

namespace dft\FoapiBundle\EventListener;

// As per: https://symfonybricks.com/en/brick/custom-exception-page-404-not-found-and-other-exceptions
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KernelExceptionListener {
    private $templating;
    private $kernel;

    /**
     * Sets injected services.
     * @param EngineInterface $templating
     * @param $kernel
     */
    public function __construct(EngineInterface $templating, $kernel) {
        $this->kernel = $kernel;
        $this->templating = $templating;
    }

    /**
     * Only handles 404 errors.
     * TODO: Add other error handlers.
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event) {
        if ($event->getException() instanceof NotFoundHttpException) {
            // Set JSON response.
            $response = new Response();
            $response->setContent(
                $this->templating->render(
                    'dftFoapiBundle:Common:404.json.twig',
                    array( "message" => $event->getException()->getMessage() )
                )
            );
            $response->setStatusCode(404);
            $event->setResponse($response);
        }

        // ...else return a standard error message.
    }
}