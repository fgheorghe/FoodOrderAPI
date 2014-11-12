<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PrinterActivityController extends Controller
{
    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;
        // Get the printer activity service.
        $printerActivityService = $this->container->get('dft_foapi.printer_activity');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $printerActivityService->fetchAll(
                        $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
                        array(
                            "start" => $query->get('start'),
                            "limit" => $query->get('limit')
                        )
                    )
            )
        );
    }
}
