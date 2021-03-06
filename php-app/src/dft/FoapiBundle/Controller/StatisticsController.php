<?php

namespace dft\FoapiBundle\Controller;

class StatisticsController extends BaseController
{
    public function monthlyVisitorsAction() {
        // Get the statistics service.
        $statisticsService = $this->container->get('dft_foapi.statistics');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $statisticsService->getMonthlyVisitors(
                        $this->getAuthenticatedUserIdAndSubAccountIds()
                    )
        ));
    }

    public function monthlyOrderValuesAction() {
        // Get the statistics service.
        $statisticsService = $this->container->get('dft_foapi.statistics');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $statisticsService->getMonthlyOrderValues(
                        $this->getAuthenticatedUserIdAndSubAccountIds()
                    )
            ));
    }

    public function monthlySellingProductsAction() {
        // Get the statistics service.
        $statisticsService = $this->container->get('dft_foapi.statistics');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $statisticsService->getMonthlySellingProducts(
                        $this->getAuthenticatedUserIdAndSubAccountIds()
                    )
            ));
    }

    public function monthlyPostcodesAction() {
        // Get the statistics service.
        $statisticsService = $this->container->get('dft_foapi.statistics');

        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
                "data" => $statisticsService->getMonthlyPostcodes(
                        $this->getAuthenticatedUserIdAndSubAccountIds()
                    )
            ));
    }

    public function recordVisitorAction() {
        // Get the statistics service.
        $statisticsService = $this->container->get('dft_foapi.statistics');

        // Authenticate the user.
        $statisticsService->addVisitor(
            $this->container->get("request")->get("ip_address"),
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId()
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }
}