<?php

namespace dft\FoapiBundle\Controller;

class CustomerController extends BaseController
{
    public function listAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $customerService->fetchAll(
                    $this->getAuthenticatedUserIdAndSubAccountIds(),
                    array(
                        "start" => $query->get('start'),
                        "limit" => $query->get('limit'),
                        // Default combo filter.
                        "name" => $query->get('query')
                    )
                )
            )
        );
    }

    public function verifyPasswordAction() {
        // _POST values.
        $request = $this->container->get("request");

        // Get the customer service.
        $customerService = $this->container->get('dft_foapi.customer');

        // Check if the username (email) / password combination is valid.
        $customer = $customerService->verifyPassword(
            $request->get('username'),
            $request->get('password')
        );

        $data = array(
            "success" => $customer === false ? false : true
        );

        if ($customer !== false) {
            $data["customer"] = $customer;
        }

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $data
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
            $request->get('password'),
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
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $customerId,
            $request->get('name'),
            $request->get('email'),
            $request->get('post_code'),
            $request->get('address'),
            $request->get('phone_number'),
            $request->get('password'),
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
            $this->getAuthenticatedUserIdAndSubAccountIds(),
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
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $customerId
        );

        // TODO: Return proper status code if failed.
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}
