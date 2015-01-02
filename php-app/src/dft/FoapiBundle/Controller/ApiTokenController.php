<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use dft\FoapiBundle\Other\Constants;

class ApiTokenController extends Controller
{
    public function generateAction()
    {
        // Get the API token service.
        $apiTokenService = $this->container->get('dft_foapi.api_token');
        $apiTokenService->generateAndSave($this->container->get('dft_foapi.login')->getAuthenticatedUserId());

        // TODO: Add error handling.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function getAction()
    {
        // Get the API token service.
        $apiTokenService = $this->container->get('dft_foapi.api_token');
        // TODO: Review this line as it may be redundant.
        $apiTokenService->getForUserId($this->container->get('dft_foapi.login')->getAuthenticatedUserId());

        return $this->render(
            'dftFoapiBundle:Common:data.json.twig',
            array(
                "data" => $apiTokenService->getForUserId(
                        $this->container->get('dft_foapi.login')->getAuthenticatedUserId()
                    )
            )
        );
    }

    // This should only be allowed for configured ips!
    public function tokensAction($domainNameOrAlias) {
        // TODO: Make list of IPs configurable!
        if(!in_array($this->container->get('request')->getClientIp(),
            array(
                "127.0.0.1",
                "184.107.103.19",
                "192.168.0.101",
                "192.168.0.103",
                "192.168.0.100"
            )
        )) {
            $response = new Response();

            $response->setContent($this
                    ->container
                    ->get('twig')
                    ->loadTemplate('dftFoapiBundle:Common:failure.json.twig')
                    ->render(array("reason" => Constants::LOGIN_FAILURE_CODE)));

            $response->setStatusCode(401);

           return $response;
        }

        // Get to api token service.
        $apiTokenService = $this->container->get('dft_foapi.api_token');

        // Return tokens.
        return $this->render(
            'dftFoapiBundle:Common:data.json.twig',
            array(
                "data" => $apiTokenService->getForDomainNameOrAlias(
                        $domainNameOrAlias
                    )
            )
        );
    }
}
