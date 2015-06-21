<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class StripePaymentSettingsController extends Controller
{
    public function getAction()
    {
        // Get the service.
        $stripePaymentSettingsService = $this->container->get('dft_foapi.stripe_payment_settings');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $stripePaymentSettingsService->fetchOne(
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
        $stripePaymentSettingsService = $this->container->get('dft_foapi.stripe_payment_settings');

        // Update settings.
        $stripePaymentSettingsService->updateStripePaymentSettings(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('stripe_secret_key'),
            $request->get('stripe_publishable_key')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
