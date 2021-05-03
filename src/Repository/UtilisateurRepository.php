<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method Utilisateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utilisateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utilisateur[]    findAll()
 * @method Utilisateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct(ManagerRegistry $registry, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($registry, Utilisateur::class);
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getUserFromDiscordOauth(
        string $discordID,
        string $discordUsername,
        string $email
    ): ?Utilisateur
    {
        $user = $this->findOneBy([
            'email' => $email
        ]);

        if(!$user){
            return null;
        }
        if($user->getDiscordID() !== $discordID){
            $user = $this->updateUserWithDiscordData($discordID, $discordUsername, $user);
        }

        return $user;
    }

    private function updateUserWithDiscordData(string $discordID, string $discordUsername, Utilisateur $user): Utilisateur
    {
        $user->setDiscordID($discordID);
        $user->setDiscordUsername($discordUsername);
        $this->_em->flush();

        return $user;
    }

    public function createUserFromDiscordOAuth(
        string $discordID,
        string $discordUsername,
        string $email,
        string $randomPassword
    ): Utilisateur
    {
        $user = new Utilisateur();

        $user->setDiscordID($discordID)
             ->setDiscordUsername($discordUsername)
             ->setEmail($email)
             ->setRoles(["ROLE_USER"])
             ->setIsVerified(true)
             ->setPseudo($discordUsername)
             ->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user,
                    $randomPassword
                )
            );
        $this->_em->persist($user);
        $this->_em->flush();

        return $user;

    }
}
