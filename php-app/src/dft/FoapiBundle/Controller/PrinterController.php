<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PrinterController extends Controller
{
    public function callbackAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Get the printer service.
        $printerService = $this->container->get('dft_foapi.printer');

        // Update order.
        $printerService->updateOrder(
            $query->get('u', false), // Username
            $query->get('p', false), // Password
            $query->get('o', false), // Order id
            $query->get('a', false), // Account id
            $query->get('m', false), // Printer message
            $query->get('dt', false), // Delivery time
            $query->get('ak', false) // Accepted / rejected
        );

        return $this->render('dftFoapiBundle:Common:printer-callback.html.twig');
    }

    public function pendingAction() {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Get the printer service.
        $printerService = $this->container->get('dft_foapi.printer');

        // Get pending printer orders, as printer response.
        $response = $printerService->pendingOrders(
            $query->get('u', false), // Username
            $query->get('p', false), // Password
            $query->get('a', false) // Account id
        );

        return $this->render('dftFoapiBundle:Common:pending-printer-orders.html.twig', array( "data" => $response ));
    }
}
