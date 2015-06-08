<?php

namespace dft\FoapiBundle\Controller;

class EmailTemplatesController extends BaseController
{
    public function getAction()
    {
        // Get the restaurant service.
        $emailTemplatesService = $this->container->get('dft_foapi.email_templates');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $emailTemplatesService->fetchOne(
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
        $emailTemplatesService = $this->container->get('dft_foapi.email_templates');

        // Update settings.
        $emailTemplatesService->updateEmailTemplates(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('order_accepted_email_subject'),
            $request->get('order_accepted_email_content'),
            $request->get('order_rejected_email_subject'),
            $request->get('order_rejected_email_content')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
