<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'required' => $options['is_new'] ?? true,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => $options['is_new'] ?? true ? [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ] : [],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                    'User' => 'ROLE_USER',
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'placeholder' => 'Select a role',
            ]);

        // Convert single role value to array before setting on entity
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (isset($data['roles']) && !is_array($data['roles']) && $data['roles'] !== null && $data['roles'] !== '') {
                $data['roles'] = [$data['roles']];
                $event->setData($data);
            }
        });

        // Convert array to single value for display (without modifying entity)
        $builder->get('roles')->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $roles = $event->getData();
            if (is_array($roles) && !empty($roles)) {
                // Remove ROLE_USER as it's always added automatically by the entity
                $roles = array_filter($roles, fn($role) => $role !== 'ROLE_USER');
                // Get the first role (or null if empty) for the dropdown
                $primaryRole = !empty($roles) ? reset($roles) : null;
                $event->setData($primaryRole);
            } else {
                $event->setData(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => true,
        ]);
    }
}
