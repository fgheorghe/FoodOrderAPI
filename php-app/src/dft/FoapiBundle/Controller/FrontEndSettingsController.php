<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontEndSettingsController extends Controller
{
    public function getAction()
    {
        // Get the front end service.
        $frontEndSettingsService = $this->container->get('dft_foapi.front_end_settings');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $frontEndSettingsService->fetchOne(
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
        $frontEndSettingsService = $this->container->get('dft_foapi.front_end_settings');

        // Update settings.
        $frontEndSettingsService->updateFrontEndSettings(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('restaurant_description'),
            $request->get('phone_numbers'),
            $request->get('facebook_page_url'),
            $request->get('google_page_url'),
            $request->get('twitter_page_url'),
            $request->get('full_address')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
