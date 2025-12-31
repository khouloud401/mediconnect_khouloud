<?php

namespace App\Controller;

use App\Entity\{Doctor, Patient, Nurse, Specialty, Appointment, Review, Log};
use App\Form\{SpecialtyType};
use App\Repository\{DoctorRepository, PatientRepository, NurseRepository, SpecialtyRepository, AppointmentRepository, ReviewRepository, LogRepository};
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // --- DASHBOARD ---
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(DoctorRepository $dr, PatientRepository $pr, NurseRepository $nurseRepo, AppointmentRepository $ar, SpecialtyRepository $specialtyRepo, ReviewRepository $rr): Response {
        return $this->render('admin/dashboard.html.twig', [
            'totalDoctors' => $dr->count([]),
            'totalPatients' => $pr->count([]),
            'totalNurses' => $nurseRepo->count([]),
            'totalAppointments' => $ar->count([]),
            'totalSpecialties' => $specialtyRepo->count([]),
            'pendingReviews' => $rr->count(['isApproved' => false]),
            'recentAppointments' => $ar->findBy([], ['id' => 'DESC'], 10),
            'topDoctors' => $dr->findTopRatedDoctors(3),
            'topSpecialties' => $specialtyRepo->findTopSpecialties(3),
        ]);
    }

    // --- GESTION DOCTEURS (LISTE + SUPPRESSION) ---
    #[Route('/doctors', name: 'admin_doctors')]
    public function doctors(DoctorRepository $r, PaginatorInterface $p, Request $req): Response {
        $doctors = $p->paginate($r->createQueryBuilder('d')->orderBy('d.id', 'DESC'), $req->query->getInt('page', 1), 10);
        return $this->render('admin/doctors/index.html.twig', ['doctors' => $doctors]);
    }

    #[Route('/doctors/delete/{id}', name: 'admin_doctor_delete', methods: ['POST'])]
    public function deleteDoctor(Doctor $doctor): Response {
        $nom = $doctor->getNom();
        $this->em->remove($doctor);
        $this->em->flush();
        $this->addLog("Suppression du médecin : " . $nom);
        return $this->redirectToRoute('admin_doctors');
    }

    // --- GESTION INFIRMIERS (LISTE + SUPPRESSION) ---
    #[Route('/nurses', name: 'admin_nurses')]
    public function nurses(NurseRepository $r): Response {
        return $this->render('admin/nurses/index.html.twig', ['nurses' => $r->findAll()]);
    }

    #[Route('/nurses/delete/{id}', name: 'admin_nurse_delete', methods: ['POST'])]
    public function deleteNurse(Nurse $nurse): Response {
        $nom = $nurse->getNom();
        $this->em->remove($nurse);
        $this->em->flush();
        $this->addLog("Suppression de l'infirmier : " . $nom);
        return $this->redirectToRoute('admin_nurses');
    }

    // --- GESTION PATIENTS (LISTE + SUPPRESSION) ---
    #[Route('/patients', name: 'admin_patients')]
    public function patients(PatientRepository $r, PaginatorInterface $p, Request $req): Response {
        $patients = $p->paginate($r->createQueryBuilder('p')->orderBy('p.id', 'DESC'), $req->query->getInt('page', 1), 10);
        return $this->render('admin/patients/index.html.twig', ['patients' => $patients]);
    }

    #[Route('/patients/delete/{id}', name: 'admin_patient_delete', methods: ['POST'])]
    public function deletePatient(Patient $patient): Response {
        $nom = $patient->getNom();
        $this->em->remove($patient);
        $this->em->flush();
        $this->addLog("Suppression du patient : " . $nom);
        return $this->redirectToRoute('admin_patients');
    }

    // --- GESTION DES SPÉCIALITÉS ---
    #[Route('/specialties', name: 'admin_specialties')]
    public function specialties(SpecialtyRepository $r): Response {
        return $this->render('admin/specialties/index.html.twig', ['specialties' => $r->findAll()]);
    }

    #[Route('/specialties/new', name: 'admin_specialty_new')]
    #[Route('/specialties/edit/{id}', name: 'admin_specialty_edit')]
    public function formSpecialty(Specialty $specialty = null, Request $req): Response {
        if (!$specialty) $specialty = new Specialty();
        $form = $this->createForm(SpecialtyType::class, $specialty);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($specialty);
            $this->em->flush();
            $this->addLog("Action sur spécialité : " . $specialty->getNom());
            return $this->redirectToRoute('admin_specialties');
        }
        return $this->render('admin/specialties/add_specialties.html.twig', [
            'form' => $form->createView(),
            'editMode' => $specialty->getId() !== null
        ]);
    }

    #[Route('/specialties/delete/{id}', name: 'admin_specialty_delete', methods: ['POST'])]
    public function deleteSpecialty(Specialty $specialty): Response {
        $nom = $specialty->getNom();
        $this->em->remove($specialty);
        $this->em->flush();
        $this->addLog("Suppression de la spécialité : " . $nom);
        return $this->redirectToRoute('admin_specialties');
    }

    // --- RENDEZ-VOUS ---
    #[Route('/appointments', name: 'admin_appointments')]
    public function appointments(AppointmentRepository $r, Request $req): Response {
        $doctorId = $req->query->get('doctor');
        $patientId = $req->query->get('patient');
        if ($doctorId) {
            $appointments = $r->findBy(['doctor' => $doctorId], ['dateTime' => 'DESC']);
        } elseif ($patientId) {
            $appointments = $r->findBy(['patient' => $patientId], ['dateTime' => 'DESC']);
        } else {
            $appointments = $r->findAll();
        }
        return $this->render('admin/rendez_vous/index.html.twig', ['appointments' => $appointments]);
    }

    // --- GESTION DES AVIS ---
    #[Route('/reviews', name: 'admin_reviews')]
    public function reviews(ReviewRepository $r): Response {
        return $this->render('admin/avis/index.html.twig', ['reviews' => $r->findAll()]);
    }

    #[Route('/reviews/delete/{id}', name: 'admin_review_delete', methods: ['POST'])]
    public function deleteReview(Review $review): Response {
        $info = "Avis de " . $review->getPatient()->getNom();
        $this->em->remove($review);
        $this->em->flush();
        $this->addLog("Suppression de l'avis : " . $info);
        $this->addFlash('success', 'Avis supprimé avec succès.');
        return $this->redirectToRoute('admin_reviews');
    }

    // --- GESTION DES LOGS ---
    #[Route('/logs', name: 'admin_logs')]
    public function viewLogs(LogRepository $logRepo): Response {
        $allLogs = $logRepo->findBy([], ['createdAt' => 'DESC']);
        $logsDoctors = []; $logsPatients = []; $logsNurses = []; $logsAdmin = [];

        foreach ($allLogs as $log) {
            $msg = strtolower($log->getAction());

            if (str_contains($msg, 'médecin') || str_contains($msg, 'docteur')) {
                $logsDoctors[] = $log;
            }
            if (str_contains($msg, 'patient')) {
                $logsPatients[] = $log;
            }
            if (str_contains($msg, 'infirmier')) {
                $logsNurses[] = $log;
            }

            if (str_contains($msg, 'suppression') || str_contains($msg, 'création') || str_contains($msg, 'action') || str_contains($msg, 'avis')) {
                $logsAdmin[] = $log;
            }
        }

        return $this->render('admin/logs/index.html.twig', [
            'logsDoctors' => $logsDoctors, 'logsPatients' => $logsPatients,
            'logsNurses' => $logsNurses, 'logsAdmin' => $logsAdmin,
        ]);
    }

    private function addLog(string $message): void {
        $log = new Log();
        $log->setAction($message);
        $log->setUserEmail($this->getUser()->getUserIdentifier());
        $log->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($log);
        $this->em->flush();
    }
}