<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedFromDiscordOAuthEvent extends Event
{

    public const SEND_EMAIL_WITH_PASSWORD = "user_created_from_oauth_event.send_email_with_password";

    private string $email;
    private string $randomPassword;

    /**
     * UserCreatedFromDiscordOAuthEvent constructor.
     * @param $email
     * @param string $randomPassword
     */
    public function __construct(string $email, string $randomPassword)
    {
        $this->email = $email;
        $this->randomPassword = $randomPassword;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRandomPassword(): string
    {
        return $this->randomPassword;
    }
}