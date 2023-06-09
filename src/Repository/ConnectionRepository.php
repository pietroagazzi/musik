<?php

namespace App\Repository;

use App\Entity\Connection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Connection>
 *
 * @method Connection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Connection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Connection[]    findAll()
 * @method Connection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectionRepository extends ServiceEntityRepository
{
	/**
	 * @param ManagerRegistry $registry
	 */
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Connection::class);
	}

	/**
	 * @param Connection $entity
	 * @param bool $flush
	 * @return void
	 */
	public function save(Connection $entity, bool $flush = false): void
	{
		$this->getEntityManager()->persist($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * @param Connection $entity
	 * @param bool $flush
	 * @return void
	 */
	public function remove(Connection $entity, bool $flush = false): void
	{
		$this->getEntityManager()->remove($entity);

		if ($flush) {
			$this->getEntityManager()->flush();
		}
	}

	/**
	 * returns true if an account is already exists for the given provider
	 *
	 * @param string $provider the provider name (e.g. spotify)
	 * @param string $providerUserId the id of the user in the provider
	 * @param User|null $user the user to exclude
	 * @return bool
	 * @throws NonUniqueResultException if more than one connection is found
	 */
	public function connectionAlreadyExists(string $provider, string $providerUserId, User $user = null): bool
	{
		$queryBuilder = $this->createQueryBuilder('c')
			->andWhere('c.provider = :provider')
			->andWhere('c.provider_user_id = :providerUserId')
			->setParameter('provider', $provider)
			->setParameter('providerUserId', $providerUserId);


		// if a user is given, exclude it
		if ($user) {
			$queryBuilder
				->andWhere('c.user != :user')
				->setParameter('user', $user);
		}

		return (bool)$queryBuilder
			->getQuery()
			->getOneOrNullResult();
	}

	//    /**
	//     * @return Connection[] Returns an array of Connection objects
	//     */
	//    public function findByExampleField($value): array
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->orderBy('c.id', 'ASC')
	//            ->setMaxResults(10)
	//            ->getQuery()
	//            ->getResult()
	//        ;
	//    }

	//    public function findOneBySomeField($value): ?Connection
	//    {
	//        return $this->createQueryBuilder('c')
	//            ->andWhere('c.exampleField = :val')
	//            ->setParameter('val', $value)
	//            ->getQuery()
	//            ->getOneOrNullResult()
	//        ;
	//    }
}
