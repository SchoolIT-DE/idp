<?php

namespace App\Form;

use App\Entity\RegistrationCode;
use App\Entity\UserType as UserTypeEntity;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportRegistrationCodeType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('code', TextType::class, [
                'label' => 'label.code'
            ])
            ->add('username', EmailType::class, [
                'label' => 'label.username',
                'required' => false
            ])
            ->add('usernameSuffix', TextType::class, [
                'label' => 'label.username_suffix',
                'required' => false
            ])
            ->add('firstname', TextType::class, [
                'label' => 'label.firstname',
                'required' => false
            ])
            ->add('lastname', TextType::class, [
                'label' => 'label.lastname',
                'required' => false
            ])
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'required' => false
            ])
            ->add('grade', TextType::class, [
                'label' => 'label.grade',
                'required' => false
            ])
            ->add('externalId', TextType::class, [
                'label' => 'label.external_id',
                'help' => 'label.external_id_help',
                'required' => false
            ])
            ->add('type', EntityType::class, [
                'class' => UserTypeEntity::class,
                'query_builder' => function(EntityRepository $repository) {
                    return $repository->createQueryBuilder('t')
                        ->orderBy('t.name', 'asc');
                },
                'choice_label' => 'name',
                'label' => 'label.user_type',
                'expanded' => false,
                'label_attr' => [
                    'class' => 'radio-custom'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefault('data_class', RegistrationCode::class);
    }
}