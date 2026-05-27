<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository de l'entité User.
 *
 * Contient les requêtes personnalisées ainsi que la gestion du
 * rehash automatique des mots de passe (PasswordUpgraderInterface).
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Met à jour (rehash) le mot de passe d'un utilisateur si nécessaire.
     * 
     * Cette méthode est appelée automatiquement par Symfony lorsque
     * l’algorithme de hachage évolue. Elle persiste simplement le
     * nouveau hash en base.
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
     *
     * Le champ "roles" étant stocké en JSON dans PostgreSQL, le filtrage
     * nécessite un cast ::text, impossible en DQL. Une requête SQL native
     * est donc utilisée pour vérifier la présence du rôle.
     * 
     * @return User|null L'utilisateur admin trouvé ou null s'il n'existe pas 
     */
    public function findAdmin(): ?User
    {       
        // Mapping Doctrine pour hydrater les résultats SQL en objets User
        // chaque ligne SQL correspond à un User alias u
        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        /** Requête SQL native pour rechercher un utilisateur contenant ROLE_ADMIN avec LIKE
         * - SELECT u.* : récupère toutes les colonnes de la table user
         * - FROM "user" u : table user, alias u
         * - WHERE u.roles::text LIKE :role : cast JSON → texte pour chercher ROLE_ADMIN
         * - LIMIT 1 : renvoie un seul utilisateur
         * - Résultat hydraté en entité User via ResultSetMapping
         */
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
     * Retourne les utilisateurs invités (non-admin) avec leurs médias chargés.
     *
     * Une seule requête Doctrine avec LEFT JOIN FETCH permet d'initialiser
     * les collections medias à l’hydratation, supprimant ainsi le problème N+1.
     *
     * Le champ "roles" étant stocké en JSON PostgreSQL, le filtrage des
     * administrateurs ne peut pas être effectué en DQL. Il est donc réalisé
     * en PHP via getRoles(), opération légère et sans impact notable.
     *
     * @return User[]
     */  
    public function findGuests(): array
    {
        /**
         * Requête DQL :
         * - FROM User u : sélectionne les utilisateurs
         * - LEFT JOIN u.medias m : joint les médias même si absents
         * - addSelect(m) : hydrate les médias dans la même requête
         * - ORDER BY u.id ASC : tri par id
         * - Résultat : utilisateurs + médias chargés en une seule requête
         */
        $users = $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        // Filtre en PHP : exclut les utilisateurs possédant ROLE_ADMIN
        return array_values(array_filter($users, static fn(User $u) => !in_array('ROLE_ADMIN', $u->getRoles(), true)));
    }

    /**
     * Retourne les invités actifs (non bloqués) avec leurs médias chargés (page front/guests.html.twig).
     *
      * LEFT JOIN FETCH charge utilisateurs et médias en une seule requête,
     * évitant le N+1. Le filtre "blocked" est appliqué en DQL, tandis que
     * l’exclusion des administrateurs est effectuée en PHP, ce qui reste
     * léger et n’impacte pas les performances.
     * 
     * @return User[]
     */
    public function findActiveGuests(): array
    {
        /**
         * Requête DQL :
         * - FROM User u : sélectionne les utilisateurs
         * - LEFT JOIN u.medias m : joint les médias
         * - addSelect(m) : hydrate les médias immédiatement
         * - WHERE u.blocked = false : filtre les utilisateurs actifs
         * - ORDER BY u.id ASC : tri par id
         * - Résultat : utilisateurs actifs (non bloqués) + médias chargés en une seule requête
         */
        $users = $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->where('u.blocked = :blocked')
            ->setParameter('blocked', false)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        // Filtre en PHP : exclut les utilisateurs possédant ROLE_ADMIN
        return array_values(array_filter($users, static fn(User $u) => !in_array('ROLE_ADMIN', $u->getRoles(), true)));
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
