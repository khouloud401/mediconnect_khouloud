<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Prescription;
use App\Form\DoctorProfileType;
use App\Form\PrescriptionType;
use App\Repository\AppointmentRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/doctor')]
#[IsGranted('ROLE_DOCTOR')]
class DoctorController extends AbstractController
{
    #[Route('/dashboard', name: 'doctor_dashboard')]
    public function dashboard(
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $doctor = $this->getUser();

        $pendingAppointments = $appointmentRepository->findBy(
            ['doctor' => $doctor, 'status' => 'pending'],
            ['dateTime' => 'ASC'],
            5
        );

        $upcomingAppointments = $appointmentRepository->findBy(
            ['doctor' => $doctor, 'status' => 'accepted'],
            ['dateTime' => 'ASC'],
            5
        );

        $totalConsultations = $appointmentRepository->count([
            'doctor' => $doctor,
            'status' => 'completed'
        ]);

        $reviews = $reviewRepository->findBy(
            ['doctor' => $doctor, 'isApproved' => true],
            ['createdAt' => 'DESC'],
            5
        );

        $averageRating = $doctor->getAverageRating();

        return $this->render('doctor/dashboard.html.twig', [
            'pendingAppointments' => $pendingAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'totalConsultations' => $totalConsultations,
            'averageRating' => $averageRating,
            'reviews' => $reviews,
        ]);
    }

    #[Route('/profile', name: 'doctor_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $doctor = $this->getUser();
        $form = $this->createForm(DoctorProfileType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('doctor_profile');
        }

        return $this->render('doctor/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/appointments', name: 'doctor_appointments')]
    public function appointments(
        AppointmentRepository $appointmentRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $doctor = $this->getUser();

        $query = $appointmentRepository->createQueryBuilder('a')
            ->where('a.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('a.dateTime', 'DESC')
            ->getQuery();

        $appointments = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('doctor/appointments.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/appointment/{id}/accept', name: 'doctor_accept_appointment')]
    public function acceptAppointment(Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($appointment->getDoctor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('accepted');
        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous accepté avec succès !');
        return $this->redirectToRoute('doctor_appointments');
    }

    #[Route('/appointment/{id}/refuse', name: 'doctor_refuse_appointment')]
    public function refuseAppointment(Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($appointment->getDoctor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('refused');
        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous refusé.');
        return $this->redirectToRoute('doctor_appointments');
    }

    #[Route('/appointment/{id}/complete', name: 'doctor_complete_appointment')]
    public function completeAppointment(Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($appointment->getDoctor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $appointment->setStatus('completed');
        $entityManager->flush();

        $this->addFlash('success', 'Rendez-vous marqué comme terminé.');
        return $this->redirectToRoute('doctor_appointments');
    }

    // ✅ Unified prescriptions page (list + create)
    #[Route('/prescriptions/{appointmentId?}', name: 'doctor_prescriptions')]
    public function prescriptions(
        ?int $appointmentId = null,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $doctor = $this->getUser();
        $form = null;

        if ($appointmentId) {
            $appointment = $entityManager->getRepository(Appointment::class)->find($appointmentId);

            if (!$appointment || $appointment->getDoctor() !== $doctor) {
                throw $this->createAccessDeniedException();
            }

            $prescription = new Prescription();
            $prescription->setAppointment($appointment);

            $form = $this->createForm(PrescriptionType::class, $prescription);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($prescription);
                $entityManager->flush();

                $this->addFlash('success', 'Ordonnance créée avec succès !');
                return $this->redirectToRoute('doctor_prescriptions');
            }
        }

        // List all prescriptions of this doctor
        $prescriptions = $entityManager->getRepository(Prescription::class)
            ->createQueryBuilder('p')
            ->join('p.appointment', 'a')
            ->where('a.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('doctor/prescriptions.html.twig', [
            'form' => $form ? $form->createView() : null,
            'prescriptions' => $prescriptions,
        ]);
    }

    #[Route('/patients', name: 'doctor_patients')]
    public function patients(): Response
    {
        $doctor = $this->getUser();

        $appointments = $doctor->getAppointments()->filter(function ($appointment) {
            return $appointment->getStatus() === 'completed';
        });

        $patients = [];
        foreach ($appointments as $appointment) {
            $patientId = $appointment->getPatient()->getId();
            if (!isset($patients[$patientId])) {
                $patients[$patientId] = $appointment->getPatient();
            }
        }

        return $this->render('doctor/patients.html.twig', [
            'patients' => array_values($patients),
        ]);
    }

    #[Route('/statistics', name: 'doctor_statistics')]
    public function statistics(
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $doctor = $this->getUser();

        $totalAppointments = $appointmentRepository->count(['doctor' => $doctor]);
        $completedAppointments = $appointmentRepository->count(['doctor' => $doctor, 'status' => 'completed']);
        $pendingAppointments = $appointmentRepository->count(['doctor' => $doctor, 'status' => 'pending']);
        $averageRating = $doctor->getAverageRating();
        $totalReviews = $reviewRepository->count(['doctor' => $doctor, 'isApproved' => true]);

        return $this->render('doctor/statistics.html.twig', [
            'totalAppointments' => $totalAppointments,
            'completedAppointments' => $completedAppointments,
            'pendingAppointments' => $pendingAppointments,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
        ]);
    }
}
