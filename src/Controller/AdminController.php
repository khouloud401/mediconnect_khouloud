<?php

namespace App\Controller;

use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\Specialty;
use App\Entity\Appointment;
use App\Entity\Review;
use App\Form\DoctorType;
use App\Form\PatientType;
use App\Form\SpecialtyType;
use App\Repository\DoctorRepository;
use App\Repository\PatientRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\AppointmentRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        DoctorRepository $doctorRepository,
        PatientRepository $patientRepository,
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository,
        SpecialtyRepository $specialtyRepository
    ): Response {
        $totalDoctors = $doctorRepository->count([]);
        $totalPatients = $patientRepository->count([]);
        $totalAppointments = $appointmentRepository->count([]);
        $pendingAppointments = $appointmentRepository->count(['status' => 'pending']);
        $totalReviews = $reviewRepository->count([]);
        $pendingReviews = $reviewRepository->count(['isApproved' => false]);

        // Top specialties
        $topSpecialties = $specialtyRepository->findAll();

        // Top rated doctors
        $topDoctors = $doctorRepository->findTopRatedDoctors(5);

        // Recent appointments
        $recentAppointments = $appointmentRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

        return $this->render('admin/dashboard.html.twig', [
            'totalDoctors' => $totalDoctors,
            'totalPatients' => $totalPatients,
            'totalAppointments' => $totalAppointments,
            'pendingAppointments' => $pendingAppointments,
            'totalReviews' => $totalReviews,
            'pendingReviews' => $pendingReviews,
            'topSpecialties' => $topSpecialties,
            'topDoctors' => $topDoctors,
            'recentAppointments' => $recentAppointments,
        ]);
    }

    // Doctors Management
    #[Route('/doctors', name: 'admin_doctors')]
    public function doctors(
        DoctorRepository $doctorRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $doctorRepository->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery();

        $doctors = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/doctors/index.html.twig', [
            'doctors' => $doctors,
        ]);
    }

    #[Route('/doctors/new', name: 'admin_doctor_new')]
    public function newDoctor(Request $request, EntityManagerInterface $entityManager): Response
    {
        $doctor = new Doctor();
        $form = $this->createForm(DoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($doctor);
            $entityManager->flush();

            $this->addFlash('success', 'Médecin ajouté avec succès !');
            return $this->redirectToRoute('admin_doctors');
        }

        return $this->render('admin/doctors/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/doctors/{id}/edit', name: 'admin_doctor_edit')]
    public function editDoctor(Doctor $doctor, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DoctorType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Médecin modifié avec succès !');
            return $this->redirectToRoute('admin_doctors');
        }

        return $this->render('admin/doctors/edit.html.twig', [
            'form' => $form->createView(),
            'doctor' => $doctor,
        ]);
    }

    #[Route('/doctors/{id}/delete', name: 'admin_doctor_delete', methods: ['POST'])]
    public function deleteDoctor(Doctor $doctor, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($doctor);
        $entityManager->flush();

        $this->addFlash('success', 'Médecin supprimé avec succès !');
        return $this->redirectToRoute('admin_doctors');
    }

    // Patients Management
    #[Route('/patients', name: 'admin_patients')]
    public function patients(
        PatientRepository $patientRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $patientRepository->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $patients = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/patients/index.html.twig', [
            'patients' => $patients,
        ]);
    }

    #[Route('/patients/{id}/delete', name: 'admin_patient_delete', methods: ['POST'])]
    public function deletePatient(Patient $patient, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($patient);
        $entityManager->flush();

        $this->addFlash('success', 'Patient supprimé avec succès !');
        return $this->redirectToRoute('admin_patients');
    }

    // Specialties Management
    #[Route('/specialties', name: 'admin_specialties')]
    public function specialties(SpecialtyRepository $specialtyRepository): Response
    {
        $specialties = $specialtyRepository->findAll();

        return $this->render('admin/specialties/index.html.twig', [
            'specialties' => $specialties,
        ]);
    }

    #[Route('/specialties/new', name: 'admin_specialty_new')]
    public function newSpecialty(Request $request, EntityManagerInterface $entityManager): Response
    {
        $specialty = new Specialty();
        $form = $this->createForm(SpecialtyType::class, $specialty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($specialty);
            $entityManager->flush();

            $this->addFlash('success', 'Spécialité ajoutée avec succès !');
            return $this->redirectToRoute('admin_specialties');
        }

        return $this->render('admin/specialties/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/specialties/{id}/edit', name: 'admin_specialty_edit')]
    public function editSpecialty(Specialty $specialty, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SpecialtyType::class, $specialty);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Spécialité modifiée avec succès !');
            return $this->redirectToRoute('admin_specialties');
        }

        return $this->render('admin/specialties/edit.html.twig', [
            'form' => $form->createView(),
            'specialty' => $specialty,
        ]);
    }

    #[Route('/specialties/{id}/delete', name: 'admin_specialty_delete', methods: ['POST'])]
    public function deleteSpecialty(Specialty $specialty, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($specialty);
        $entityManager->flush();

        $this->addFlash('success', 'Spécialité supprimée avec succès !');
        return $this->redirectToRoute('admin_specialties');
    }

    // Appointments Management
    #[Route('/appointments', name: 'admin_appointments')]
    public function appointments(
        AppointmentRepository $appointmentRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $appointmentRepository->createQueryBuilder('a')
            ->orderBy('a.dateTime', 'DESC')
            ->getQuery();

        $appointments = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/appointments/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    // Reviews Management
    #[Route('/reviews', name: 'admin_reviews')]
    public function reviews(
        ReviewRepository $reviewRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $reviewRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery();

        $reviews = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/reviews/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/reviews/{id}/approve', name: 'admin_review_approve')]
    public function approveReview(Review $review, EntityManagerInterface $entityManager): Response
    {
        $review->setIsApproved(true);
        $entityManager->flush();

        $this->addFlash('success', 'Avis approuvé avec succès !');
        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/reviews/{id}/delete', name: 'admin_review_delete', methods: ['POST'])]
    public function deleteReview(Review $review, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($review);
        $entityManager->flush();

        $this->addFlash('success', 'Avis supprimé avec succès !');
        return $this->redirectToRoute('admin_reviews');
    }
}
