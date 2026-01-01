<?php

namespace App\Form;

use App\Entity\Disponibilite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', DateTimeType::class, [
                'label' => 'Début du créneau',
                'widget' => 'single_text', // Affiche un calendrier et une horloge propres
                'attr' => ['class' => 'form-control'],
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'Fin du créneau',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            // On retire "isAvailable" et "doctor" car on les gère directement dans le Controller
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
        ]);
    }
}