<?php
namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductDetail;
use App\Entity\Brand;
use App\Entity\ClientCombination;
use App\Form\HVACTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HVACService
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    public function createForm(ClientCombination $clientcombination): FormInterface
    {
        return $this->formFactory->create(HVACTypeForm::class, $clientcombination);
    }


    public function handleApiFormSubmission(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['success' => false, 'errors' => ['Invalid or missing JSON payload.']], 400);
        }

        try {

            // Validate required fields
            foreach (['brand', 'Number_of_rooms', 'cooling_capacity', 'indoor_unit_type'] as $field) {
                if (!isset($data["hvac_type_form"][$field])) {
                    throw new \Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                }
            }

            // Fetch brands
            $brand = $this->entityManager->getRepository(Brand::class)->find($data['hvac_type_form']['brand']);
            if (!$brand) {
                throw new \Exception('Invalid brand ID.');
            }


            // add a outdoor unit too
            // if( !in_array('outdoor', $data['hvac_type_form[indoor_unit_type]'] ) ) {
            //
            //     $data['hvac_type_form[indoor_unit_type]'][] = 'outdoor';
            // }

            //Fetch and filter ProductDetails using Criteria
            $allDetails = $this->entityManager->getRepository(ProductDetail::class)->findAll();
            $collection = new ArrayCollection($allDetails);

            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('brand', (int)$data['hvac_type_form']['brand']))
                ->andWhere(Criteria::expr()->in('type', $data['hvac_type_form']['indoor_unit_type']));


            $matchingDetails = $collection->matching($criteria);

            $clientCombinationProd = [];

            foreach ($matchingDetails as $key => $matchingProd) {
                $theProduct = $this->entityManager->getRepository(Product::class)->find( $matchingProd->getProductId() );

                if( isset($theProduct) ) {

                    $clientCombinationProd[] = array(
                        'productId' =>$theProduct->getId(),
                        'name'  =>$theProduct->getName(),
                        'cooling_capacity' =>  $matchingProd->getCoolingCapacity(),
                        'type' =>  $matchingProd->getType(),
                        'brand' => $brand->getName()
                    );
                }
            }

            $combinations = $this->generateCombinations($clientCombinationProd ,  $data["hvac_type_form"]["Number_of_rooms"], $data["hvac_type_form"]["Number_of_rooms"] );
            // dd(  $combinations );


            // Create ClientCombination entity
            $clientCombo = new ClientCombination();
            $clientCombo->setBrand($brand->getName());
            $clientCombo->setNumberOfRooms((int) $data['hvac_type_form']['Number_of_rooms']);
            $clientCombo->setCoolingCapacity(json_encode($data['hvac_type_form']['cooling_capacity']));
            $clientCombo->setIndoorUnitType(json_encode($data['hvac_type_form']['indoor_unit_type']));
            $clientCombo->setDateAdded(new \DateTime());
            $clientCombo->setCombinations(json_encode($combinations));

            // Validate entity
            $errors = $this->validator->validate($clientCombo);
            if (count($errors) > 0) {
                $errorMessages = array_map(fn($e) => ['field' => $e->getPropertyPath(), 'message' => $e->getMessage()], iterator_to_array($errors));
                return new JsonResponse(['success' => false, 'errors' => $errorMessages], 400);
            }

            $this->entityManager->persist($clientCombo);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'client_combination_id' => $clientCombo->getId(),
                'combinations' => $combinations,
            ], 201);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'errors' => [$e->getMessage()]], 400);
        }
    }


    private function generateCombinations(array $items, int $min = 1, int $max = 5): array
    {
        $result = [];

        for ($i = $min; $i <= $max; $i++) {
            $combinations = $this->combinationsWithRepetition($items, $i);
            foreach ($combinations as $combo) {

                // Skip if not all types are unique
                $types = array_column($combo, 'type');
                if (count($types) !== count(array_unique($types))) {
                    continue;
                }

                // Skip if not all productIds are unique
                $ids = array_column($combo, 'productId');
                if (count($ids) !== count(array_unique($ids))) {
                    continue;
                }

                // Sort and hash by productId to ensure uniqueness
                usort($combo, fn($a, $b) => $a['productId'] <=> $b['productId']);
                $hash = implode('-', $ids);
                $result[$hash] = $combo;
            }
        }

        return array_values($result); // remove keys
    }

    private function combinationsWithRepetition(array $items, int $length): array
    {
        if ($length === 0) return [[]];

        $result = [];
        foreach ($items as $index => $item) {
            $subset = array_slice($items, $index); // allow repetition
            foreach ($this->combinationsWithRepetition($subset, $length - 1) as $combo) {
                $result[] = array_merge([$item], $combo);
            }
        }

        return $result;
    }


    function getAllclientCombinations(): array
    {
        $templateClienCombinations = [];
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('cc')
           ->from(ClientCombination::class, 'cc');

        $clientCombinations = $qb->getQuery()->getResult();

        foreach ($clientCombinations as $cCombination) {
            $templateClienCombinations[] = [
                'combinationId' => $cCombination->getId(),
                'brand' => $cCombination->getBrand(),
                'numberOfRooms' => $cCombination->getNumberOfRooms(),
                'coolingCapacity' => $cCombination->getCoolingCapacity(),
                'indoorUnitType' => $cCombination->getIndoorUnitType(),
                // 'combination' => $cCombination->getCombinations(),
            ];
        }

        return $templateClienCombinations;
    }

    public function getSelectedCombination(int $combinationId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('cc')
           ->from(ClientCombination::class, 'cc')
           ->where('cc.id = :id')
           ->setParameter('id', $combinationId);

        $cCombination = $qb->getQuery()->getOneOrNullResult();

        if (!$cCombination) {
            return []; // or throw exception
        }

        $combinations = $cCombination->getCombinations();

        return json_decode($combinations);
    }

    public function deleteSelectedCombination(int $combinationId): bool
    {
        $combination = $this->entityManager->getRepository(ClientCombination::class)->find($combinationId);

        if (!$combination) {
            throw $this->createNotFoundException('Combination not found.');
        }

        $this->entityManager->remove($combination);
        $this->entityManager->flush();

        return true;
    }

    public function exportCombinations(): StreamedResponse
    {
        $combinations = $this->entityManager->getRepository(ClientCombination::class)->findAll();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($combinations) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'ID', 'Brand', 'Number of Rooms', 'Cooling Capacity', 'Indoor Unit Type', 'Combinations', 'Date Added'
            ]);

            // Data rows
            foreach ($combinations as $combination) {
                fputcsv($handle, [
                    $combination->getId(),
                    $combination->getBrand(),
                    $combination->getNumberOfRooms(),
                    $combination->getCoolingCapacity(),
                    $combination->getIndoorUnitType(),
                    $combination->getCombinations(),
                    $combination->getDateAdded()?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        });

        $filename = 'client_combinations_' . date('Y_m_d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");

        return $response;
    }

}
