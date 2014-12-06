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
}
