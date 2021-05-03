<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Event\UserCreatedFromDiscordOAuthEvent;
use App\Repository\UtilisateurRepository;
use App\Service\PasswordGenerator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordUserProvider implements UserProviderInterface
{
    private const DISCORD_ACCESSTOKEN_ENDPOINT = 'https://discord.com/api/oauth2/token';
    private const DISCORD_USER_DATA_ENDPOINT = 'https://discord.com/api/users/@me';

    private EventDispatcherInterface $eventDispatcher;

    private HttpClientInterface $httpClient;

    private PasswordGenerator $passwordGenerator;

    private string $discordClientID;

    private string $discordClientSecret;

    private UrlGeneratorInterface $urlGenerator;

    private UtilisateurRepository $utilisateurRepository;

    /**
     * DiscordUserProvider constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param HttpClientInterface $httpClient
     * @param PasswordGenerator $passwordGenerator
     * @param string $discordClientID
     * @param string $discordClientSecret
     * @param UrlGeneratorInterface $urlGenerator
     * @param UtilisateurRepository $utilisateurRepository
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        HttpClientInterface $httpClient,
        PasswordGenerator $passwordGenerator,
        string $discordClientID,
        string $discordClientSecret,
        UrlGeneratorInterface $urlGenerator,
        UtilisateurRepository $utilisateurRepository
    ){
        $this->eventDispatcher = $eventDispatcher;
        $this->httpClient = $httpClient;
        $this->passwordGenerator = $passwordGenerator;
        $this->discordClientID = $discordClientID;
        $this->discordClientSecret = $discordClientSecret;
        $this->urlGenerator = $urlGenerator;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    public function loadUserFromDiscordOAuth(string $code): Utilisateur
    {
        $accessToken = $this->getAccessToken($code);

        $discordData = $this->getUserInformations($accessToken);

        [
            'email'     => $email,
            'id'        => $discordID,
            'username'  => $discordUsername
        ] = $discordData;

        $user = $this->utilisateurRepository->getUserFromDiscordOauth($discordID, $discordUsername, $email);
        if(!$user){
            $randomPassword = $this->passwordGenerator->generateRandomStrongPassword();

            $user = $this->utilisateurRepository->createUserFromDiscordOAuth($discordID, $discordUsername, $email, $randomPassword);
            $this->eventDispatcher->dispatch(new UserCreatedFromDiscordOAuthEvent($email, $randomPassword), UserCreatedFromDiscordOAuthEvent::SEND_EMAIL_WITH_PASSWORD);
        }

        return $user;
    }

    public function loadUserByUsername(string $discordID): Utilisateur
    {
        $user = $this->utilisateurRepository->findOneBy([
            'discordID' => $discordID
        ]);

        if(!$user){
            throw new UsernameNotFoundException('Utilisateur inexistant');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if(!$user instanceof Utilisateur || !$user->getDiscordID()){
            throw new UnsupportedUserException();
        }

        /** @var string $discordID */
        $discordID = $user->getDiscordID();

        return $this->loadUserByUsername($discordID);
    }

    public function supportsClass(string $class): bool
    {
        return Utilisateur::class === $class;
    }

    private function getAccessToken(string $code): string
    {
        $redirectURL = $this->urlGenerator->generate('app_login', [
            'discord-oauth-provider' => true
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'client_id' => $this->discordClientID,
                'client_secret' => $this->discordClientSecret,
                'code'  => $code,
                'grant_type'=> "authorization_code",
                'redirect_uri' => $redirectURL,
                'scope' => 'identify email'
            ]
        ];

        $response = $this->httpClient->request('POST', self::DISCORD_ACCESSTOKEN_ENDPOINT, $options);

        $data = $response->toArray();

        if(!$data['access_token']){
            throw new ServiceUnavailableHttpException(null, "l'authantification a échoué, veuillez réessayer.");
        }

        return $data['access_token'];
    }

    private function getUserInformations(string $accessToken): array
    {
        $options = [
          'headers' => [
              'Accept' => 'application/json',
              'Authorization' => "Bearer {$accessToken}"
          ]
        ];
        $response = $this->httpClient->request('GET', self::DISCORD_USER_DATA_ENDPOINT, $options);

        $data = $response->toArray();

        if(!$data['email'] || !$data['id'] || !$data['username']) {
            throw new ServiceUnavailableHttpException(null, "L'API de discord semble avoir un problème ou la structure de la reponse as été modifiée");
        } elseif(!$data['verified']){
            throw new HttpException(Response::HTTP_UNAUTHORIZED, "Le compte utilisateur Discord n'est pas vérifié.");
        }

        return $data;
    }

}
