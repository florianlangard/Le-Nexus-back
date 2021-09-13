<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mime\Address;

class MailerController extends AbstractController
{
    /**
     * @Route("/mailer", name="mailer")
     */
    public function sendConfirmationEmail(MailerInterface $mailer): Response
    {
        $email = (new TemplatedEmail())
        ->from('hello@nexus.com')
        ->to(new Address('test@test.com'))
        ->subject('Bienvenue sur Le Nexus!')
        ->htmlTemplate('mailer/signup.html.twig');

        $mailer->send($email);

        return $this->redirectToRoute('user_index');
    }
}
