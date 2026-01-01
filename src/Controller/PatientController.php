<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Review;
use App\Form\AppointmentType;
use App\Form\ReviewType;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use App\Repository\PrescriptionRepository;
use App\Repository\ReviewRepository;
use App\Repository\SpecialtyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient')]
#[IsGranted('ROLE_PATIENT')]
class PatientController extends AbstractController
{
    #[Route('/dashboard', name: 'patient_dashboard')]
    public function dashboard(AppointmentRepository $appointmentRepository): Response
    {
        $patient = $this->getUser();

        $appointments = $appointmentRepository->findBy(
            ['patient' => $patient],
            ['dateTime' => 'DESC'],
            5
        );

        return $this->render('patient/dashboard.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/search', name: 'patient_search_doctors')]
    public function searchDoctors(
        Request $request,
        DoctorRepository $doctorRepository,
        SpecialtyRepository $specialtyRepository,
        PaginatorInterface $paginator
    ): Response {
        $specialty = $request->query->get('specialty');
        $ville     = $request->query->get('ville');
        $nom       = $request->query->get('nom');

        $doctors = $doctorRepository->findBySearchCriteria($specialty, $ville, $nom);

        $pagination  = $paginator->paginate(
            $doctors,
            $request->query->getInt('page', 1),
            12
        );
        $specialties = $specialtyRepository->findAll();

        return $this->render('patient/search_doctors.html.twig', [
            'doctors'      => $pagination,
            'specialties'  => $specialties,
            'filters'      => [
                'specialty' => $specialty,
                'ville'     => $ville,
                'nom'       => $nom,
            ],
        ]);
    }

    #[Route('/doctor/{id}', name: 'patient_doctor_profile')]
    public function doctorProfile(Doctor $doctor): Response
    {
        return $this->render('patient/doctor_profile.html.twig', [
            'doctor' => $doctor,
        ]);
    }

    #[Route('/book/{id}', name: 'patient_book_appointment')]
    public function bookAppointment(
        Doctor $doctor,
        Request $request,
        EntityManagerInterface $entityManager,
        AppointmentRepository $appointmentRepository
    ): Response {
        $appointment = new Appointment();
        $appointment->setDoctor($doctor);
        $appointment->setPatient($this->getUser());

        // --- LOGIQUE DES DATES BLEUES (OCCUPÉES) ---
        // On récupère les rendez-vous déjà "Acceptés" pour ce docteur
        $appointmentsOccupes = $appointmentRepository->findBy([
            'doctor' => $doctor,
            'status' => 'accepted'
        ]);

        $datesBloquees = [];
        foreach ($appointmentsOccupes as $app) {
            if ($app->getDateTime()) {
                // Formatage compatible avec l'input datetime-local (YYYY-MM-DDTHH:mm)
                $datesBloquees[] = $app->getDateTime()->format('Y-m-d\TH:i');
            }
        }

        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification de sécurité finale pour éviter les doublons
            $dateChoisie = $appointment->getDateTime()->format('Y-m-d\TH:i');
            if (in_array($dateChoisie, $datesBloquees)) {
                $this->addFlash('danger', 'Désolé, ce créneau vient d\'être réservé (Bleu). Veuillez en choisir un autre.');
                return $this->redirectToRoute('patient_book_appointment', ['id' => $doctor->getId()]);
            }

            $entityManager->persist($appointment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de rendez-vous a été envoyée avec succès !');
            return $this->redirectToRoute('patient_appointments');
        }

        return $this->render('patient/book_appointment.html.twig', [
            'form'   => $form->createView(),
            'doctor' => $doctor,
            'datesBloquees' => $datesBloquees, // Envoi au Twig pour le JavaScript
        ]);
    }

    #[Route('/appointments', name: 'patient_appointments')]
    public function appointments(
        AppointmentRepository $appointmentRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $patient = $this->getUser();

        $query = $appointmentRepository->createQueryBuilder('a')
            ->where('a.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('a.dateTime', 'DESC')
            ->getQuery();

        $appointments = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('patient/appointments.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/prescriptions', name: 'patient_prescriptions')]
    public function prescriptions(
        PrescriptionRepository $prescriptionRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $patient = $this->getUser();

        $query = $prescriptionRepository->createQueryBuilder('p')
            ->join('p.appointment', 'a')
            ->where('a.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();

        $prescriptions = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('patient/prescriptions.html.twig', [
            'prescriptions' => $prescriptions,
        ]);
    }

    #[Route('/reviews', name: 'patient_reviews')]
    public function reviews(
        ReviewRepository $reviewRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $patient = $this->getUser();

        $query = $reviewRepository->createQueryBuilder('r')
            ->where('r.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery();

        $reviews = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('patient/reviews.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/appointment/{id}/review', name: 'patient_add_review')]
    public function addReview(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($appointment->getPatient() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($appointment->getStatus() !== 'completed') {
            $this->addFlash('error', 'Vous ne pouvez laisser un avis que pour un rendez-vous terminé.');
            return $this->redirectToRoute('patient_appointments');
        }

        $review = new Review();
        $review->setPatient($this->getUser());
        $review->setDoctor($appointment->getDoctor());

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès !');
            return $this->redirectToRoute('patient_appointments');
        }

        return $this->render('patient/add_review.html.twig', [
            'form'        => $form->createView(),
            'appointment' => $appointment,
        ]);
    }

    #[Route('/doctor/{id}/review', name: 'patient_write_review')]
    public function writeReview(
        Doctor $doctor,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $review = new Review();
        $review->setPatient($this->getUser());
        $review->setDoctor($doctor);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès !');
            return $this->redirectToRoute('patient_reviews');
        }

        return $this->render('patient/add_review_direct.html.twig', [
            'form'   => $form->createView(),
            'doctor' => $doctor,
        ]);
    }

    #[Route('/prescription/{id}/download', name: 'patient_download_prescription')]
    public function downloadPrescription(int $id): Response
    {
        $this->addFlash('info', 'Fonctionnalité de téléchargement en cours de développement.');
        return $this->redirectToRoute('patient_appointments');
    }
}