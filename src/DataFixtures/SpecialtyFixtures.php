<?php
// src/DataFixtures/SpecialtyFixtures.php
namespace App\DataFixtures;

use App\Entity\Specialty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SpecialtyFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $specialtyNames = ['Cardiologue', 'Dentiste', 'Dermatologue', 'Pédiatre'];

        foreach ($specialtyNames as $nom) {
            $specialty = new Specialty();
            $specialty->setNom($nom); // <-- ici
            $manager->persist($specialty);
        }

        $manager->flush();
    }

}
