<?php

namespace App\Form;

use App\Entity\ClientCombination;
use App\Entity\Brand;
use App\Entity\Product;
use App\Entity\ProductDetail;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HVACTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brand' , EntityType::class, [
                'class' => Brand::class,
                'choice_label' => function (?Brand $brand): string {
                    return $brand ? $brand->getName() : '';
                },
                'choice_value' => function (?Brand $brand): ?string {
                    return $brand ? (string) $brand->getId() : null;
                },
                'placeholder' => 'Select a product',
                'required' => true,
            ])
            ->add('Number_of_rooms', IntegerType::class, [
                'required' => false,
                'mapped' => false, // <-- this tells Symfony it's not linked to the entity
                'label' => 'Number of Rooms',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                    'placeholder' => 'Add number of rooms'
                ]
            ])
            ->add('cooling_capacity', EntityType::class, [
                'class' => ProductDetail::class,
                'choice_label' => function (?ProductDetail $prod_det): string {
                    return $prod_det ? $prod_det->getCoolingCapacity() : '';
                },
                'choice_value' => function (?ProductDetail $prod_det): ?string {
                    return $prod_det ? (string) $prod_det->getCoolingCapacity() : null;
                },
                'placeholder' => 'Select a capacity',
                'required' => true,
            ])
            ->add('indoor_unit_type', EntityType::class, [
                'class' => ProductDetail::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                             ->where('p.type != :excluded')
                             ->setParameter('excluded', 'outdoor');
                },
                'choice_label' => fn(?ProductDetail $prod_det) => $prod_det ? $prod_det->getType() : '',
                'choice_value' => fn(?ProductDetail $prod_det) => $prod_det ? (string) $prod_det->getType() : null,
                'placeholder' => 'Select a type',
                'required' => true,
            ]);

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClientCombination::class,
            'csrf_protection' => false, // Important for API
            'allow_extra_fields' => true,
        ]);
    }
}
