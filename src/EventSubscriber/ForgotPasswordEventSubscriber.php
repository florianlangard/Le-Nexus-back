<?php

// src/EventSubscriber/ForgotPasswordEventSubscriber.php
namespace App\EventSubscriber;

// ...

use App\Repository\UserRepository;
use Twig\Environment;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use CoopTilleuls\ForgotPasswordBundle\Event\CreateTokenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use CoopTilleuls\ForgotPasswordBundle\Event\UpdatePasswordEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ForgotPasswordEventSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $twig;
    private $em;
    private $userPasswordHasher;

    public function __construct(MailerInterface $mailer, Environment $twig, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->em = $em;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public static function getSubscribedEvents()
    {
        return [
            // Symfony 4.3 and inferior, use 'coop_tilleuls_forgot_password.create_token' event name
            CreateTokenEvent::class => 'onCreateToken',
            UpdatePasswordEvent::class => 'onUpdatePassword',
        ];
    }

    public function onCreateToken(CreateTokenEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        
        $message = (new Email())
            ->from('contactnexus@gmail.com')
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->html($this->twig->render(
                'reset_password/mail.html.twig',
                [
                    'reset_password_url' => sprintf('http://localhost:8000/forgot-password/%s', $passwordToken->getToken()),
                ]
            ));
        if (0 === $this->mailer->send($message)) {
            throw new \RuntimeException('Unable to send email');
        }
    }

    public function onUpdatePassword(UpdatePasswordEvent $event)
    {
        $passwordToken = $event->getPasswordToken();
        $user = $passwordToken->getUser();
        
        // Hashing password here
        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $event->getPassword());
        $user->setPassword($hashedPassword);

        // $user->setPassword($event->getPassword());
        $this->em->flush($user);
    }
}
