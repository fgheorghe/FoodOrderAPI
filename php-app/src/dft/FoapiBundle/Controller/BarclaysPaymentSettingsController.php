<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BarclaysPaymentSettingsController extends Controller
{
    public function getAction()
    {
        // Get the service.
        $barclaysPaymentSettingsService = $this->container->get('dft_foapi.barclays_payment_settings');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $barclaysPaymentSettingsService->fetchOne(
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

        // Get the service.
        $barclaysPaymentSettingsService = $this->container->get('dft_foapi.barclays_payment_settings');

        // Update settings.
        $barclaysPaymentSettingsService->updateBarclaysPaymentSettings(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('pspid'),
            $request->get('sha1'),
            $request->get('live_payment_system')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
