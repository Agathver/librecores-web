<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\SourceRepo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ContributorRepository
 *
 * Extends the default repository with custom functionality.
 *
 * @author Amitosh Swain Mahapatra <amitosh.swain@gmail.com>
 */
class ContributorRepository extends ServiceEntityRepository
{
    /**
     * {@inheritDoc}
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Contributor::class);
    }

    /**
     * Get contributor for a source repository from an email.
     *
     * Creates and returns a new entity, if a name is supplied and the email does not exist.
     *
     * @param SourceRepo  $repo  repository to search against
     * @param string      $email email of the contributor to search for
     * @param null|string $name  name of the contributor
     *
     * @return Contributor|null
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getContributorForRepository(SourceRepo $repo, string $email, ?string $name = null): ?Contributor
    {
        $contributor = $this->findOneBy(
            [
                'sourceRepo' => $repo,
                'email' => $email,
            ]
        );

        // create and return and entity only when the name is specified
        if (null === $contributor && $name !== null) {
            $contributor = new Contributor();
            $contributor->setName($name)
                ->setEmail($email)
                ->setSourceRepo($repo);

            $this->getEntityManager()->persist($contributor);
            $this->getEntityManager()->flush();     // we flush here as this value is queried elsewhere
            $this->getEntityManager()->refresh($contributor);
        }

        return $contributor;
    }

    /**
     * Gets all contributors for the given repository
     *
     * @param SourceRepo $repo repository to query for
     *
     * @return array
     */
    public function getContributorsForRepository(SourceRepo $repo)
    {
        return $this->findBy(
            [
                'sourceRepo' => $repo,
            ]
        );
    }

    /**
     * Gets the number of contributors of the given repository
     *
     * @param SourceRepo $repo repository to query for
     *
     * @return mixed
     *
     * @throws NonUniqueResultException
     */
    public function getContributorCountForRepository(SourceRepo $repo): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(1)')
            ->where('c.sourceRepo = :repo')
            ->setParameter('repo', $repo)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Gets top n contributors from repositories by commit count, lines added and lines removed
     *
     * @param SourceRepo $repo  repository to query for
     * @param int        $count number of top contributors to return
     *
     * @return array
     */
    public function getTopContributorsForRepository(SourceRepo $repo, int $count)
    {
        return $this->createQueryBuilder('c')
            ->join('c.commits', 'co')
            ->select('c')
            ->addSelect('COUNT(co.id) AS HIDDEN commits')
            ->addSelect('SUM(co.linesAdded) AS HIDDEN linesAdded')
            ->addSelect('SUM(co.linesRemoved) AS HIDDEN linesRemoved')
            ->groupBy('c.id')
            ->having('c.sourceRepo = :repo')
            ->addOrderBy('commits', 'DESC')
            ->addOrderBy('linesAdded', 'DESC')
            ->addOrderBy('linesRemoved', 'ASC')
            ->setParameter('repo', $repo)
            ->getQuery()
            ->setMaxResults($count)
            ->getResult();
    }
}
