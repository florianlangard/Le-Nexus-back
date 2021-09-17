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

    public function sendConfirmationEmail($target)
    {
        $email = (new TemplatedEmail())
        ->from('officialnexuscontact@gmail.com')
        ->to(new Address($target))
        ->subject('Bienvenue sur Le Nexus!')
        ->htmlTemplate('mailer/signup.html.twig');

        $this->mailer->send($email);

        return true;
    }

    public function sendContactEmail($sender, $content)
    {
        $email = (new TemplatedEmail())
        ->from($sender)
        ->to(new Address('officialnexuscontact@gmail.com'))
        ->subject('formulaire de contact : '. $sender)
        ->htmlTemplate('mailer/contact.html.twig')
        ->context([
            'sender' => $sender,
            'content' => $content
        ]);

        $this->mailer->send($email);

        return true;
    }
}