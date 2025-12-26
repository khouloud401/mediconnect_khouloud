<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Prescription;
use App\Entity\Nurse;
use App\Entity\Task;
use App\Form\DoctorProfileType;
use App\Form\PrescriptionType;
use App\Form\NurseType;
use App\Repository\AppointmentRepository;
use App\Repository\ReviewRepository;
use App\Repository\NurseRepository;
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

    // --- GESTION DES INFIRMIERS ---

    #[Route('/nurses', name: 'doctor_nurses_list', methods: ['GET'])]
    public function listNurses(NurseRepository $nurseRepository): Response
    {
        $doctor = $this->getUser();

        return $this->render('doctor/index.html.twig', [
            'nurses' => $nurseRepository->findBy(['doctor' => $doctor]),
        ]);
    }

    #[Route('/add-nurse', name: 'doctor_add_existing_nurse', methods: ['POST'])]
    public function addExistingNurse(Request $request, NurseRepository $nurseRepository, EntityManagerInterface $em): Response
    {
        $email = $request->request->get('email');
        $nurse = $nurseRepository->findOneBy(['email' => $email]);

        if (!$nurse) {
            $this->addFlash('danger', 'Aucun infirmier trouvé avec cet email.');
            return $this->redirectToRoute('doctor_nurses_list');
        }

        $doctor = $this->getUser();
        $nurse->setDoctor($doctor);

        $em->persist($nurse);
        $em->flush();

        $this->addFlash('success', 'L\'infirmier ' . $nurse->getName() . ' a été ajouté à votre liste.');

        return $this->redirectToRoute('doctor_nurses_list');
    }

    #[Route('/nurse/assign-task/{id}', name: 'doctor_assign_task', methods: ['POST'])]
    public function assignTask(Request $request, Nurse $nurse, EntityManagerInterface $entityManager): Response
    {
        $description = $request->request->get('description');
        $patientName = $request->request->get('patientName');

        if ($description && $patientName) {
            $task = new Task();
            $task->setDescription($description);
            $task->setPatientName($patientName);
            $task->setStatus('En cours');
            $task->setCreatedAt(new \DateTimeImmutable());
            $task->setNurse($nurse);
            $task->setDoctor($this->getUser());

            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche envoyée avec succès.');
        }

        return $this->redirectToRoute('doctor_nurses_list');
    }

    #[Route('/nurse/delete/{id}', name: 'doctor_nurse_delete', methods: ['POST'])]
    public function deleteNurse(Request $request, Nurse $nurse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nurse->getId(), $request->request->get('_token'))) {
            $nurse->setDoctor(null);
            $entityManager->flush();
            $this->addFlash('success', 'Infirmier retiré de votre liste.');
        }
        return $this->redirectToRoute('doctor_nurses_list');
    }

    // --- FIN GESTION INFIRMIERS ---

    #[Route('/profile', name: 'doctor_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $doctor = $this->getUser();
        $form = $this->createForm(DoctorProfileType::class, $doctor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour !');
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
        $this->addFlash('success', 'Rendez-vous accepté.');
        return $this->redirectToRoute('doctor_dashboard');
    }

    /**
     * AJOUT : Fonction pour refuser un rendez-vous (Corrige l'erreur RouteNotFound)
     */
    #[Route('/appointment/{id}/refuse', name: 'doctor_refuse_appointment')]
    public function refuseAppointment(Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($appointment->getDoctor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $appointment->setStatus('refused');
        $entityManager->flush();
        $this->addFlash('danger', 'Rendez-vous refusé.');
        return $this->redirectToRoute('doctor_dashboard');
    }
    #[Route('/appointment/{id}/complete', name: 'doctor_complete_appointment')]
    public function completeAppointment(Appointment $appointment, EntityManagerInterface $entityManager): Response
    {
        if ($appointment->getDoctor() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $appointment->setStatus('completed'); // Change le statut en 'completed'
        $entityManager->flush();

        $this->addFlash('success', 'La consultation est terminée.');

        return $this->redirectToRoute('doctor_appointments');
    }

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
                return $this->redirectToRoute('doctor_prescriptions');
            }
        }

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
        $appointments = $doctor->getAppointments()->filter(fn($a) => $a->getStatus() === 'completed');
        $patients = [];
        foreach ($appointments as $a) {
            $patients[$a->getPatient()->getId()] = $a->getPatient();
        }
        return $this->render('doctor/patients.html.twig', ['patients' => array_values($patients)]);
    }

    #[Route('/statistics', name: 'doctor_statistics')]
    public function statistics(
        AppointmentRepository $appointmentRepository,
        ReviewRepository $reviewRepository
    ): Response {
        $doctor = $this->getUser();
        return $this->render('doctor/statistics.html.twig', [
            'totalAppointments' => $appointmentRepository->count(['doctor' => $doctor]),
            'completedAppointments' => $appointmentRepository->count(['doctor' => $doctor, 'status' => 'completed']),
            'pendingAppointments' => $appointmentRepository->count(['doctor' => $doctor, 'status' => 'pending']),
            'averageRating' => $doctor->getAverageRating(),
            'totalReviews' => $reviewRepository->count(['doctor' => $doctor, 'isApproved' => true]),
        ]);
    }
}