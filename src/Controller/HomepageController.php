<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductDetail;
use App\Entity\ClientCombination;
use App\Repository\ProductRepository;
use App\Service\HVACService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(HVACService $hvacService): Response
    {
        $clientCombination = new ClientCombination();
        $form = $hvacService->createForm($clientCombination);

        return $this->render('homepage.html.twig', [
            'controller_name' => 'HomepageController',
            'form' => $form->createView(),
        ]);
    }


    public function submit(Request $request, HVACService $hvacService): JsonResponse
    {
        return $hvacService->handleApiFormSubmission($request);
    }


}
