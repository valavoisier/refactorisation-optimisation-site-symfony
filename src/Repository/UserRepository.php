<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository de l'entité User (requêtes personnalisées + gestion du password upgrade)
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Met à jour (rehash) le mot de passe d'un utilisateur si nécessaire.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // Vérifie que l'objet reçu est bien une entité User
        // dans le cas contraire, une exception est levée pour éviter des erreurs de type
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);// Met à jour le mot de passe de l'utilisateur
        $this->getEntityManager()->persist($user);// Marque l'entité comme devant être sauvegardée
        $this->getEntityManager()->flush();// Exécute la requête de mise à jour dans la base de données
    }

    /**
     * Retourne l'utilisateur admin (ROLE_ADMIN)
     */
    public function findAdmin(): ?User
    {
        // Utilisation d'une requête native pour rechercher un utilisateur avec le rôle ROLE_ADMIN
        // Le champ roles est un tableau stocké en JSON, on utilise la syntaxe PostgreSQL pour vérifier si le rôle est présent
        // Mapping Doctrine pour hydrater les résultats SQL en objets User
        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        // Requête SQL native pour trouver un utilisateur avec le rôle ROLE_ADMIN avec LIKE
        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT u.* FROM "user" u WHERE u.roles::text LIKE :role LIMIT 1',
            $rsm
        );
        // Paramètre recherché dans la colonne roles
        $query->setParameter('role', '%ROLE_ADMIN%');

        // Retourne un seul résultat ou null si aucun admin n'est trouvé
        return $query->getOneOrNullResult();
    }

    /** 
     * Retourne tous les utilisateurs "invités" (guests) qui ne sont pas admin (ROLE_ADMIN)
     * @return User[] 
     */
    public function findGuests(): array
    {
       
        // Requête SQL native pour trouver tous les utilisateurs qui n'ont pas le rôle ROLE_ADMIN avec NOT LIKE
        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        // Requête SQL : sélectionne les utilisateurs sans ROLE_ADMIN avec NOT LIKE
        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT u.* FROM "user" u WHERE u.roles::text NOT LIKE :role ORDER BY u.id ASC',
            $rsm
        );
        // Paramètre recherché dans la colonne roles -exclut les admins
        $query->setParameter('role', '%ROLE_ADMIN%');

        // Retourne un tableau d'objets User correspondant aux invités
        return $query->getResult();
    }

    /** 
     * Retourne les invités actifs (non bloqués) pour l'affichage front
     * @return User[]
     */
    public function findActiveGuests(): array
    {
        // Requête SQL native pour trouver tous les utilisateurs qui n'ont pas le rôle ROLE_ADMIN et qui ne sont pas bloqués
        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        // Requête SQL : sélectionne les utilisateurs sans ROLE_ADMIN et non bloqués
        $query = $this->getEntityManager()->createNativeQuery(
            'SELECT u.* FROM "user" u WHERE u.roles::text NOT LIKE :role AND u.blocked = false ORDER BY u.id ASC',
            $rsm
        );
        // Paramètre recherché dans la colonne roles -exclut les admins
        $query->setParameter('role', '%ROLE_ADMIN%');

        // Retourne un tableau d'objets User correspondant aux invités actifs
        return $query->getResult();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
