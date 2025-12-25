<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Entity\Task; // Ajouté pour gérer les soins
use App\Form\NurseType;
use App\Repository\TaskRepository;
use App\Repository\NurseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/nurse')]
final class NurseController extends AbstractController
{
    /**
     * Dashboard de l'infirmier : Planning et Tâches (Soins)
     */
    #[Route('', name: 'app_nurse_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $nurse = $this->getUser();

        if (!$nurse instanceof Nurse) {
            return $this->redirectToRoute('app_login');
        }

        $tasks = $taskRepository->findBy(['nurse' => $nurse], ['createdAt' => 'DESC']);

        return $this->render('nurse/dashboard.html.twig', [
            'nurse' => $nurse,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Valider un soin (Terminer une tâche)
     */
    #[Route('/task/{id}/complete', name: 'app_nurse_complete_task', methods: ['POST'])]
    public function completeTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        $task->setStatus('Terminé');
        $entityManager->flush();

        $this->addFlash('success', 'Le soin pour ' . $task->getPatientName() . ' a été validé.');

        return $this->redirectToRoute('app_nurse_index');
    }

    /**
     * Modification du profil par l'infirmier
     */
    #[Route('/{id}/edit', name: 'app_nurse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Nurse $nurse, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $nurse) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce profil.");
        }

        $form = $this->createForm(NurseType::class, $nurse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_nurse_index');
        }

        return $this->render('nurse/edit.html.twig', [
            'nurse' => $nurse,
            'form' => $form,
        ]);
    }

    /**
     * Détails d'un infirmier
     */
    #[Route('/{id}', name: 'app_nurse_show', methods: ['GET'])]
    public function show(Nurse $nurse): Response
    {
        return $this->render('nurse/show.html.twig', [
            'nurse' => $nurse,
        ]);
    }

    /**
     * Suppression
     */
    #[Route('/{id}', name: 'app_nurse_delete', methods: ['POST'])]
    public function delete(Request $request, Nurse $nurse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$nurse->getId(), $request->request->get('_token'))) {
            $entityManager->remove($nurse);
            $entityManager->flush();
            $this->addFlash('success', 'Infirmier retiré avec succès.');
        }

        return $this->redirectToRoute('doctor_nurses_list');
    }
}