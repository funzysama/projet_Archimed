<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class DiscordAuthenticator extends AbstractGuardAuthenticator
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    private UrlGeneratorInterface $urlGenerator;
    private DiscordUserProvider $discordUserProvider;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        UrlGeneratorInterface $urlGenerator,
        DiscordUserProvider $discordUserProvider
    )
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->urlGenerator = $urlGenerator;
        $this->discordUserProvider = $discordUserProvider;
    }

    public function supports(Request $request): bool
    {
        return $request->query->has('discord-oauth-provider');
    }

    public function getCredentials(Request $request): array
    {
        $state = $request->query->get('state');
        //dump($this->csrfTokenManager->isTokenValid(new CsrfToken('oauth-discord-sf', $state)));
        if(!$state || !$this->csrfTokenManager->isTokenValid(new CsrfToken('oauth-discord-SF', $state))){
            throw new AccessDeniedException('Not this way !!');
        }

        return [
            'code' => $request->query->get('code')
        ];

    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?Utilisateur
    {
        if($credentials === null){
            return null;
        }

        return $this->discordUserProvider->loadUserFromDiscordOAuth($credentials['code']);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentification refusée'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('main_index'));
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentification requise'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
