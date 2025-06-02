<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Brand;
use App\Entity\ProductDetail;
use App\Entity\ProductAssociatedBrand;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-database',
    description: 'Seeds the database with fake products and details.'
)]
class SeedDatabaseCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();

        $custom_brands = ['Daikin', 'Mitsubishi', 'LG'] ;
        $brands_new_id = [];

        for ($i = 0; $i < count($custom_brands); $i++) {
            $brand = new brand();
            $brand->setName( $custom_brands[$i] );

            $this->entityManager->persist($brand);

            $this->entityManager->flush();
            $brands_new_id[] =  $brand->getId();
        }

        for ($i = 0; $i < 100; $i++) {
            $product = new Product();
            $product->setName($faker->bothify('HVAC-??##'));
            $product->setManufacturerId($faker->numberBetween(1, 5));

            $this->entityManager->persist($product);

            // Exactly one ProductDetail per Product
            $detail = new ProductDetail();
            $detail->setCoolingCapacity($faker->randomElement([9000, 12000, 18000, 24000]));
            $detail->setType($faker->randomElement( ['wall-mounted', 'ceiling cassette', 'floor-standing', 'ducted', 'console', 'outdoor'] ));
            $detail->setBrand( $brands_new_id[ $faker->numberBetween(0,  count($custom_brands)-1) ] );
            $detail->setProduct($product);

            $this->entityManager->flush();
            //
            // $prod_assoc = new ProductAssociatedBrand();
            // $prod_assoc->setProductId( $product->getId() );
            // $prod_assoc->setBrandId( $brands_new_id[ $faker->numberBetween(0, 4) ] );
            // $prod_assoc->setDateAdded(new \DateTime());
            //

            $this->entityManager->persist($detail);
            // $this->entityManager->persist($prod_assoc);
        }


        $this->entityManager->flush();
        $output->writeln('<info>Database seeded successfully!</info>');

        return Command::SUCCESS;
    }
}
