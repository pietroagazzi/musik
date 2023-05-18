<?php

namespace App\Repository;

use App\Entity\Follow;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Follow>
 *
 * @method Follow|null find($id, $lockMode = null, $lockVersion = null)
 * @method Follow|null findOneBy(array $criteria, array $orderBy = null)
 * @method Follow[]    findAll()
 * @method Follow[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FollowRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Follow::class);
	}

	public function follow(User $follower, User $followed): void
	{
		$entity = new Follow();
		$entity->setFollower($follower);
		$entity->setFollowed($followed);

		$this->save($entity, true);
	}

	public function save(Follow $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	public function unfollow(User $follower, User $followed): void
	{
		$follow = $this->findOneBy([
			'follower' => $follower,
			'followed' => $followed
		]);

		if ($follow) {
			$this->remove($follow, true);
		}
	}

	public function remove(Follow $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	//    /**
	//     * @return Follower[] Returns an array of Follower objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('f')
	//            ->andWhere('f.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('f.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Follower
	//    {
	//        return $this->createQueryBuilder('f')
	//            ->andWhere('f.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
