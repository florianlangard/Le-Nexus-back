<?php

namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;

class Mailing
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendConfirmationEmail()
    {
        $email = (new TemplatedEmail())
        ->from('hello@nexus.com')
        ->to(new Address('test@test.com'))
        ->subject('Bienvenue sur Le Nexus!')
        ->htmlTemplate('mailer/signup.html.twig');

        $this->mailer->send($email);

        return true;
    }
}