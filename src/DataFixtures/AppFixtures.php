<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\Specialty;
use App\Entity\Appointment;
use App\Entity\Review;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création des Spécialités
        $specialties = [
            ['nom' => 'Médecine Générale', 'description' => 'Consultation générale et suivi médical'],
            ['nom' => 'Cardiologie', 'description' => 'Spécialiste des maladies cardiovasculaires'],
            ['nom' => 'Dermatologie', 'description' => 'Spécialiste des maladies de la peau'],
            ['nom' => 'Pédiatrie', 'description' => 'Spécialiste de la santé des enfants'],
            ['nom' => 'Gynécologie', 'description' => 'Spécialiste de la santé féminine'],
            ['nom' => 'Ophtalmologie', 'description' => 'Spécialiste des yeux et de la vision'],
            ['nom' => 'ORL', 'description' => 'Oto-rhino-laryngologie'],
            ['nom' => 'Dentiste', 'description' => 'Soins dentaires'],
        ];

        $specialtyObjects = [];
        foreach ($specialties as $spec) {
            $specialty = new Specialty();
            $specialty->setNom($spec['nom']);
            $specialty->setDescription($spec['description']);
            $manager->persist($specialty);
            $specialtyObjects[] = $specialty; // Stockage simple
        }

        // 2. Création de l'Admin
        $admin = new Admin();
        $admin->setEmail('admin@mediconnect.com');
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setPhone('0600000000');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // 3. Création des Docteurs
        $doctors = [];
        $doctorData = [
            ['nom' => 'Dupont', 'prenom' => 'Jean', 'ville' => 'Paris', 'experience' => '15 ans'],
            ['nom' => 'Martin', 'prenom' => 'Marie', 'ville' => 'Lyon', 'experience' => '10 ans'],
            ['nom' => 'Bernard', 'prenom' => 'Pierre', 'ville' => 'Marseille', 'experience' => '20 ans'],
            ['nom' => 'Dubois', 'prenom' => 'Sophie', 'ville' => 'Toulouse', 'experience' => '8 ans'],
            ['nom' => 'Laurent', 'prenom' => 'Michel', 'ville' => 'Nice', 'experience' => '12 ans'],
        ];

        foreach ($doctorData as $index => $data) {
            $doctor = new Doctor();
            $doctor->setEmail(strtolower($data['prenom'] . '.' . $data['nom']) . '@mediconnect.com');
            $doctor->setNom($data['nom']);
            $doctor->setPrenom($data['prenom']);
            $doctor->setPhone('061000' . str_pad($index, 4, '0', STR_PAD_LEFT));
            $doctor->setVille($data['ville']);
            // Utilisation d'un index modulo pour ne jamais dépasser le nombre de spécialités
            $doctor->setSpecialty($specialtyObjects[$index % count($specialtyObjects)]);
            $doctor->setExperience($data['experience']);
            $doctor->setDescription('Médecin expérimenté et dévoué.');
            $doctor->setHoraires('Lundi-Vendredi: 9h-18h');
            $doctor->setPassword($this->passwordHasher->hashPassword($doctor, 'doctor123'));
            $manager->persist($doctor);
            $doctors[] = $doctor;
        }

        // 4. Création des Patients
        $patients = [];
        $patientData = [
            ['nom' => 'Leroy', 'prenom' => 'Thomas', 'ville' => 'Paris'],
            ['nom' => 'Moreau', 'prenom' => 'Julie', 'ville' => 'Lyon'],
            ['nom' => 'Simon', 'prenom' => 'Lucas', 'ville' => 'Marseille'],
        ];

        foreach ($patientData as $index => $data) {
            $patient = new Patient();
            $patient->setEmail(strtolower($data['prenom'] . '.' . $data['nom']) . '@email.com');
            $patient->setNom($data['nom']);
            $patient->setPrenom($data['prenom']);
            $patient->setPhone('072000' . str_pad($index, 4, '0', STR_PAD_LEFT));
            $patient->setVille($data['ville']);
            $patient->setAdresse(($index + 1) . ' rue de la Paix, ' . $data['ville']);
            $patient->setPassword($this->passwordHasher->hashPassword($patient, 'patient123'));
            $manager->persist($patient);
            $patients[] = $patient;
        }

        // On sauvegarde tout une première fois pour générer les IDs
        $manager->flush();

        // 5. Création des Rendez-vous (si patients et docteurs existent)
        if (!empty($patients) && !empty($doctors)) {
            foreach ($patients as $patient) {
                for ($i = 0; $i < 2; $i++) {
                    $appointment = new Appointment();
                    $appointment->setPatient($patient);
                    $appointment->setDoctor($doctors[$i]);
                    $appointment->setDateTime(new \DateTime('+' . ($i + 1) . ' days'));
                    $appointment->setMotif('Consultation de contrôle');
                    $appointment->setStatus($i === 0 ? 'pending' : 'accepted');
                    $manager->persist($appointment);
                }
            }
        }

        // 6. Création des Reviews (si patients et docteurs existent)
        if (!empty($patients) && !empty($doctors)) {
            foreach ($doctors as $doctor) {
                $review = new Review();
                $review->setDoctor($doctor);
                $review->setPatient($patients[0]);
                $review->setRating(rand(4, 5));
                $review->setComment('Excellent médecin, très professionnel.');
                $review->setIsApproved(true);
                $manager->persist($review);
            }
        }

        // Sauvegarde finale
        $manager->flush();
    }
}