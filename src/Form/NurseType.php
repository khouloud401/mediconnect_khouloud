<?php

namespace App\Form;

use App\Entity\Nurse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NurseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control']
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control']
            ])
            ->add('experience', IntegerType::class, [
                'label' => 'Expérience (années)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'choices' => [
                    'Homme' => 'Homme',
                    'Femme' => 'Femme'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('shift', ChoiceType::class, [
                'label' => 'Équipe (Shift)',
                'choices' => [
                    'Matin (8h-16h)' => 'morning',
                    'Soir (16h-00h)' => 'evening',
                    'Nuit (00h-8h)' => 'night'
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false
            ])
            ->add('teamNumber', IntegerType::class, [
                'label' => 'Numéro d\'équipe',
                'attr' => ['class' => 'form-control', 'min' => 1],
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Nurse::class,
        ]);
    }
    #[Route('/task/{id}/complete', name: 'app_nurse_complete_task', methods: ['POST'])]
    public function completeTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        // On change le statut de la tâche
        $task->setStatus('Terminé');
        $entityManager->flush();

        $this->addFlash('success', 'Soin validé avec succès !');

        return $this->redirectToRoute('app_nurse_index');
    }
}