<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Doctor;
use App\Entity\Nurse;
use App\Form\RegistrationNurseType;
use App\Form\RegistrationPatientType;
use App\Form\RegistrationDoctorType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(): Response
    {
        return $this->render('registration/index.html.twig');
    }

    #[Route('/register/patient', name: 'app_register_patient')]
    public function registerPatient(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $patient = new Patient();
        $form = $this->createForm(RegistrationPatientType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $patient->setPassword(
                $passwordHasher->hashPassword(
                    $patient,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($patient);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte patient a été créé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_patient.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/register/doctor', name: 'app_register_doctor')]
    public function registerDoctor(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $doctor = new Doctor();
        $form = $this->createForm(RegistrationDoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. On récupère le fichier depuis le formulaire
            $file = $form->get('diploma')->getData();

            // 2. On le met dans l'entité (Vich s'occupera du reste !)
            if ($file) {
                $doctor->setDiplomaFile($file);
            }

            // 3. On hache le mot de passe
            $doctor->setPassword(
                $passwordHasher->hashPassword(
                    $doctor,
                    $form->get('plainPassword')->getData()
                )
            );

            // 4. On enregistre (Vich déplace le fichier à ce moment-là)
            $entityManager->persist($doctor);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé ! Veuillez attendre l\'approbation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_doctor.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    #[Route('/register/nurse', name: 'app_register_nurse')]
    public function registerNurse(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $nurse = new Nurse();
        $form = $this->createForm(RegistrationNurseType::class, $nurse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nurse->setPassword(
                $passwordHasher->hashPassword(
                    $nurse,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($nurse);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte infirmier a été créé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_nurse.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }


}

