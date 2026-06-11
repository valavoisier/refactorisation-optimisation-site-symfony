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

        $user->setPassword($newHashedPassword); // Met à jour le mot de passe de l'utilisateur
        $this->getEntityManager()->persist($user); // Marque l'entité comme devant être sauvegardée
        $this->getEntityManager()->flush(); // Exécute la requête de mise à jour dans la base de données
    }

    /**
     * Retourne l'utilisateur admin (ROLE_ADMIN).
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
         * - Résultat hydraté en entité User via ResultSetMapping.
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
     * administrateurs ne peut pas être effectué en DQL. On récupère d'abord
     * l'entité admin via findAdmin(), puis on l'exclut directement en DQL
     * avec `u != :admin` (traduit en WHERE u.id != ? par Doctrine).
     * Ina n'est donc jamais chargée, ni ses médias.
     *
     * @return User[]
     */
    public function findGuests(): array
    {
        $admin = $this->findAdmin();

        /**
         * Requête DQL :
         * - FROM User u : sélectionne tous les utilisateurs
         * - LEFT JOIN u.medias m : joint les médias de chaque utilisateur (même si aucun média n'existe, l'utilisateur est quand même récupéré)
         * - addSelect(m) : hydrate les médias dans la même requête (évite le N+1)
         * - WHERE u != :admin : exclut l'administrateur (Ina) en SQL (WHERE u.id != ?) — ses médias et albums ne sont jamais chargés
         * - ORDER BY u.id ASC : tri par id croissant des utilisateurs
         * - Résultat : utilisateurs + médias chargés en une seule requête
         */
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->orderBy('u.id', 'ASC');

        if (null !== $admin) {
            $qb->where('u != :admin')
                ->setParameter('admin', $admin);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les invités actifs (non bloqués) avec leurs médias chargés (page front/guests.html.twig).
     *
     * LEFT JOIN FETCH charge utilisateurs et médias en une seule requête,
     * évitant le N+1. Ina est exclue directement en DQL via `u != :admin`
     * (traduit en WHERE u.id != ? par Doctrine) : ni son enregistrement User,
     * ni ses médias ne sont chargés par le LEFT JOIN.
     *
     * @return User[]
     */
    public function findActiveGuests(): array
    {
        $admin = $this->findAdmin();

        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->where('u.blocked = :blocked')
            ->setParameter('blocked', false)
            ->orderBy('u.id', 'ASC');

        if (null !== $admin) {
            $qb->andWhere('u != :admin')
                ->setParameter('admin', $admin);
        }

        return $qb->getQuery()->getResult();
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
