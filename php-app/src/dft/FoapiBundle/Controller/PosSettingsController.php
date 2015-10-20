<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PosSettingsController extends Controller
{
    public function getAction()
    {
        // Get the restaurant service.
        $posSettingsService = $this->container->get('dft_foapi.pos_settings');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $posSettingsService->fetchOne(
                    $this->container->get('dft_foapi.login')->getAuthenticatedUserId()
                )
            )
        );
    }

    // TODO: Return proper status code if failed.
    // TODO: Allow for multiple branches.
    // TODO: Verify delivery range data type.
    public function updateAction()
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the restaurant service.
        $posSettingsService = $this->container->get('dft_foapi.pos_settings');

        // Update settings.
        $posSettingsService->updatePosSettings(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('query_interval'),
            $request->get('printer_device_path')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
