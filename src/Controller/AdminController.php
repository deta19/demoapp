<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductDetail;
use App\Entity\Brand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\HVACService;
use App\Entity\ClientCombination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/client_combinations', name: 'app_admin_client_combinations')]
    public function client_combinations( HVACService $hvacService ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $clientCombinationsData = $hvacService->getAllclientCombinations();

        return $this->render('admin/clientcombinations.html.twig', [
            'clientCombinationsData' => $clientCombinationsData,
        ]);
    }

    #[Route('/admin/client_combinations/{id}/combination', name: 'app_admin_client_combinations_combination')]
    public function selected_client_combinations(int $id, HVACService $hvacService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $selectedCombination = $hvacService->getSelectedCombination($id);

        return $this->render('admin/selectedclientcombination.html.twig', [
            'selectedCombination' => $selectedCombination,
        ]);
    }

    #[Route('/admin/client_combinations/delete/{id}', name: 'app_admin_client_combination_delete')]
    public function delete_client_combinations(int $id, HVACService $hvacService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $selectedCombination = $hvacService->deleteSelectedCombination($id);

        return $this->redirectToRoute('app_admin_client_combinations');
    }

    #[Route('/admin/client_combinations/edit/{id}', name: 'app_admin_client_combination_edit')]
    public function edit_client_combinations(int $id,
                                                Request $request,
                                                EntityManagerInterface $entityManager
                                                ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $combination = $entityManager->getRepository(ClientCombination::class)->find($id);

        if (!$combination) {
            throw $this->createNotFoundException('Combination not found.');
        }

        // You can create a Symfony form here, or manually update values:
        if ($request->isMethod('POST')) {
            $combination->setBrand($request->request->get('brand'));
            $combination->setNumberOfRooms((int) $request->request->get('number_of_rooms'));
            $combination->setCoolingCapacity($request->request->get('cooling_capacity'));
            $combination->setIndoorUnitType($request->request->get('indoor_unit_type'));
            $combination->setCombinations($request->request->get('combinations'));
            $combination->setDateAdded(new \DateTime()); // or keep old value

            $entityManager->flush();

            $this->addFlash('success', 'Combination updated successfully.');

            return $this->redirectToRoute('app_admin_client_combinations'); // adjust to your route
        }

        return $this->render('admin/editcombinations.html.twig', [
            'combination' => $combination,
        ]);
    }

    #[Route('/admin/products', name: 'app_admin_products')]
    public function products(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $templateProducts = [];

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('p', 'pd')
           ->from(Product::class, 'p')
           ->join('p.details', 'pd');

        $products = $qb->getQuery()->getResult();
        $templateProducts = [];

        $brands = $this->entityManager->getRepository(Brand::class)->findAll();

        $brandArray = [];

        foreach ($brands as $brand) {
            $brandArray[ $brand->getId() ] =  $brand->getName();
        }

        foreach ($products as $product) {
            $details = $product->getDetails();

            $templateProducts[] = [
                'name' => $product->getName(),
                'coolingCapacity' => $details ? $details->getCoolingCapacity() : null,
                'brand' => $details ? $brandArray[ $details->getBrand() ] : null,
                'type' => $details ? $details->getType() : null,
            ];
        }


        return $this->render('admin/products.html.twig', [
           'products' => $templateProducts,
        ]);
    }

    #[Route('/admin/client_combinations/export', name: 'admin_combinations_export')]
    public function exportCombinations(HVACService $hvacService): Response
    {
         return $hvacService->exportCombinations();
    }
}
