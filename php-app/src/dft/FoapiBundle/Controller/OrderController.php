<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OrderController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;
        // Get the order service.
        $orderService = $this->container->get('dft_foapi.order');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $orderService->fetchAll(
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
