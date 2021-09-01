<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'membre' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN'
                ],
                'multiple' => false,
                'expanded' => true,
            ])
            ->add('pseudo')
            ->add('steamId')
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                
                $form = $event->getForm();
                // On récup le user
                $user = $event->getData();
                // Si le user n'a pas d'id, c'est qu'il n'a jamais été "persisté"
                if($user->getId() === null) {
                    // Si nouveau user
                    $form->add('password', RepeatedType::class, [
                        'type' => PasswordType::class,
                        'invalid_message' => 'Les mots de passe ne correspondent pas.',
                        'required' => true,
                        'first_options'  => [
                            'constraints' => new NotBlank(),
                            'label' => 'Mot de passe',
                            'help' => 'Rappel: Un mot de passe fort mange sa soupe et a au moins huit caractères, dont au moins une lettre, un chiffre et un caractère spécial.'
                        ],
                        'second_options' => ['label' => 'Répéter le mot de passe'],
                    ]);

                } else {

                    $form->add('password', RepeatedType::class, [
                        'type' => PasswordType::class,
                        'invalid_message' => 'Les mots de passe ne correspondent pas.',
                        'mapped' => false,
                        'first_options'  => [
                            'attr' => [
                                'placeholder' => 'Laissez ce champ vide si inchangé',
                            ],
                            'label' => 'Mot de passe',
                            'help' => 'Rappel: Un mot de passe fort mange sa soupe et a au moins huit caractères, dont au moins une lettre, un chiffre et un caractère spécial.'
                        ],
                        'second_options' => ['label' => 'Répéter le mot de passe'],
                    ]);
                }

            })
            // ->add('password', PasswordType::class)
            //->add('steamUsername')
            //->add('steamAvatar')
            //->add('visibilityState')
            // ->add('isLogged')
            // ->add('createdAt')
            // ->add('updatedAt')
            //->add('mood')
        ;
        $builder
            ->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    // transform the array to a string
                    return count($rolesArray)? $rolesArray[0]: null;
                },
                function ($rolesString) {
                    // transform the string back to an array
                    return [$rolesString];
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
