<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
        // TODO: Restrict by ip address.
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
