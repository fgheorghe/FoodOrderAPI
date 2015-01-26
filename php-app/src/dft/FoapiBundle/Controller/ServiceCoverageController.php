<?php

namespace dft\FoapiBundle\Controller;

class ServiceCoverageController extends BaseController
{
    public function checkAction($restaurantPostCode, $customerPostCode, $deliveryRange)
    {
        // Check service coverage.
        $covered = $this->container->get('dft_foapi.service_coverage')->isCovered(
            $restaurantPostCode,
            $customerPostCode,
            $deliveryRange
        );

        // TODO: Move version to a config variable.
        return $this->render('dftFoapiBundle:Common:' . ($covered ? 'success' : 'failure') . '.json.twig');
    }
}
