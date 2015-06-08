<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SMSNotificationSettingsController extends Controller
{
    public function getAction()
    {
        // Get the service.
        $SMSNotificationSettingsService = $this->container->get('dft_foapi.sms_notification_settings');

        return $this->render('dftFoapiBundle:Common:data-no-numeric-check.json.twig', array(
                "data" => $SMSNotificationSettingsService->fetchOne(
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
        $SMSNotificationSettingsService = $this->container->get('dft_foapi.sms_notification_settings');

        // Update settings.
        $SMSNotificationSettingsService->updateSMSNotificationSettings(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('sms_gateway_username'),
            $request->get('sms_gateway_password'),
            $request->get('enable_sms_notifications_on_online_orders'),
            (string) $request->get('sms_order_notification_recipient')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
