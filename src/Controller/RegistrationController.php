<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\Doctor;
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
            $doctor->setPassword(
                $passwordHasher->hashPassword(
                    $doctor,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($doctor);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte médecin a été créé avec succès ! Veuillez attendre l\'approbation de l\'administrateur.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_doctor.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
