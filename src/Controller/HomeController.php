<?php

namespace App\Controller;

use App\Repository\DoctorRepository;
use App\Repository\SpecialtyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        DoctorRepository $doctorRepository,
        SpecialtyRepository $specialtyRepository
    ): Response {
        $topDoctors = $doctorRepository->findTopRatedDoctors(6);
        $specialties = $specialtyRepository->findAll();

        return $this->render('home/index.html.twig', [
            'topDoctors' => $topDoctors,
            'specialties' => $specialties,
        ]);
    }
}
