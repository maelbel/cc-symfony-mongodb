<?php

namespace App\Form\Type;

use App\Document\Reservation;
use App\Document\Hotel;
use App\Document\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hotel', DocumentType::class, [
                'class' => Hotel::class,
                'choice_label' => 'hotelName',
                'label' => 'Hotel'
            ])
            ->add('room', DocumentType::class, [
                'class' => Room::class,
                'choice_label' => 'roomCode',
                'label' => 'Room'
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date'
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'End Date'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
