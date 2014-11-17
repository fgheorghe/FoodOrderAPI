<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CustomerController extends Controller
{
    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $customerService->fetchAll(
                    $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
                    array(
                        "start" => $query->get('start'),
                        "limit" => $query->get('limit')
                    )
                )
            )
        );
    }

    public function createAction()
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        $customerService->createCustomer(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $request->get('name'),
            $request->get('email'),
            $request->get('post_code'),
            $request->get('address'),
            $request->get('phone_number'),
            $request->get('verified')
        );

        // TODO: Return proper response if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function updateAction($customerId)
    {
        // _POST values.
        $request = $this->container->get("request");

        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        $customerService->updateCustomer(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $customerId,
            $request->get('name'),
            $request->get('email'),
            $request->get('post_code'),
            $request->get('address'),
            $request->get('phone_number'),
            $request->get('verified')
        );

        // TODO: Return proper response if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function verifyAction($customerId)
    {
        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        $customerService->verifyCustomer(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $customerId
        );

        // TODO: Return proper status code if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function unverifyAction($customerId)
    {
        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        $customerService->unverifyCustomer(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $customerId
        );

        // TODO: Return proper status code if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
