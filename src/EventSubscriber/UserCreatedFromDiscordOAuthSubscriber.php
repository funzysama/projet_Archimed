<?php


namespace App\EventSubscriber;


use App\Event\UserCreatedFromDiscordOAuthEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class UserCreatedFromDiscordOAuthSubscriber implements EventSubscriberInterface
{

    private LoggerInterface $discordOAuthLogger;
    private MailerInterface $mailer;

    public function __construct(LoggerInterface $discordOAuthLogger, MailerInterface $mailer)
    {
        $this->discordOAuthLogger = $discordOAuthLogger;
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedFromDiscordOAuthEvent::SEND_EMAIL_WITH_PASSWORD => 'sendEmailWithPassword'
        ];
    }

    public function sendEmailWithPassword(UserCreatedFromDiscordOAuthEvent $event): void
    {
        $email = $event->getEmail();

        $randomPass = $event->getRandomPassword();

        $mail = (new Email())
            ->from(new Address('mailer@archi-med.fr', 'Archi-med eMail Bot'))
            ->to($email)
            ->subject('discord creation')
            ->html('<div><p>username: '.$email.'</p><p>password: '.$randomPass.'</p>');
        try{
            $this->mailer->send($mail);
        }catch (TransportExceptionInterface $e){
            throw new TransportException("Mail pas envoyé !".$e);
        }

    }
}