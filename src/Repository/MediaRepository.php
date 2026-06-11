<?php

namespace App\Repository;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array<string,mixed> $criteria, array<string,string>|null $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array<string,mixed> $criteria, array<string,string>|null $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    // Injection du ManagerRegistry et initialisation du repository Media
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /** @return Media[] */
    public function findByAlbum(Album $album): array
    {
        return $this->findBy(['album' => $album]);
    }

    /** @return Media[] */
    public function findByUser(?User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Retourne une page de médias avec l’utilisateur et l’album associés
     * chargés dans la même requête.
     *
     * Usage :
     * - Administration : affichage des médias avec leurs relations.
     * - Invité connecté : filtrage sur ses propres médias.
     *
     * Détails de la requête:
     * - m : entité Media principale.
     * - LEFT JOIN m.user u : récupère l’utilisateur lié au média.
     * - LEFT JOIN m.album a : récupère l’album lié au média.
     * - addSelect(u, a) : inclut user et album dans l’hydratation.
     * - WHERE m.user = :user : filtre les médias d’un invité connecté.
     * - ORDER BY u.id, m.id : tri stable par utilisateur puis par média.
     * - LIMIT/OFFSET : pagination appliquée côté SQL.
     * 
     * Résultat : 
     * Une page de médias avec leurs relations chargées en une requête,
     * Pagination propre par invité, et affichage complet en admin.
     * 
     * @return Media[]
     */
    public function findPaginatedWithRelations(?User $user, int $page, int $perPage = 25): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')->addSelect('u')
            ->leftJoin('m.album', 'a')->addSelect('a')
            ->orderBy('u.id', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        if ($user !== null) {
            $qb->where('m.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Media[] Returns an array of Media objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Media
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
