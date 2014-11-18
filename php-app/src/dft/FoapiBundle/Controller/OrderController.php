<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class OrderController extends Controller
{
    public function getAction($orderId)
    {
        // Get the order service.
        $orderService = $this->container->get('dft_foapi.order');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $orderService->fetchOne(
                        $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
                        $orderId
                    )
            )
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function createAction()
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the order service.
        $orderService = $this->container->get('dft_foapi.order');

        $orderService->createOrder(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('total_price'),
            $request->get('delivery_address'),
            $request->get('notes'),
            $request->get('payment_status'),
            $request->get('order_type'),
            $request->get('customer_type'),
            $request->get('customer_name'),
            $request->get('customer_phone_number'),
            $request->get('delivery_type'),
            $request->get('discount')
        );

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
                            "limit" => $query->get('limit'),
                            "order_type" => $query->get('order_type'),
                            "delivery_type" => $query->get('delivery_type'),
                            "customer_type" => $query->get('customer_type'),
                            "payment_status" => $query->get('payment_status'),
                            "customer_name" => $query->get('customer_name'),
                            "phone_number" => $query->get('phone_number'),
                            "interval" => $query->get('interval')
                        )
                    )
            )
        );
    }
}
