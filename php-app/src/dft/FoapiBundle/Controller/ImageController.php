<?php

namespace dft\FoapiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class ImageController extends BaseController
{
    public function uploadAction()
    {
        // TODO: Add security checks!!!

        // Prepare the file to upload.
        $file = $this->getRequest()->files->get('image');

        // Load the image service.
        $imageService = $this->container->get("dft_foapi.image");
        $imageService->upload(
            $this->container->get('dft_foapi.login')->getAuthenticatedUserId(),
            $file->getClientOriginalName(),
            file_get_contents($file->getPathname()),
            $file->getMimeType()
        );
        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function listAction() {
        // Load the image service.
        $imageService = $this->container->get("dft_foapi.image");
        return $this->render('dftFoapiBundle:Common:data.json.twig', array(
            "data" => $imageService->fetchAll(
                    $this->getAuthenticatedUserIdAndSubAccountIds()
                )
            )
        );
    }

    public function deleteAction($imageId) {
        // Load the image service.
        $this->container->get("dft_foapi.image")->delete(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $imageId
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function updateAction($imageId) {
        // _POST values.
        $request = $this->container->get("request");

        $this->container->get("dft_foapi.image")->update(
            $this->getAuthenticatedUserIdAndSubAccountIds(),
            $imageId,
            $request->get('link'),
            $request->get('type')
        );

        return $this->render('dftFoapiBundle:Common:success.json.twig');
    }

    public function viewAction($imageId) {
        // Load the image service.
        $image = $this->container->get("dft_foapi.image")->fetchOne(
            $imageId
        );

        $response = new Response();
        $response->setContent($image['content']);
        $response->headers->set('Content-Type', $image['mime_type']);

        return $response;
    }
}