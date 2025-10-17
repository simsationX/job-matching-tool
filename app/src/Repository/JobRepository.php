<?php

namespace App\Repository;

use App\Entity\GeoCity;
use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @return array<int, array{id: int, title: string, description: string}>
     */
    public function findBatch(int $offset = 0, int $limit = 1000): array
    {
        $qb = $this->createQueryBuilder('job')
            ->select('job.id, job.position, job.description')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    public function countJobsCreatedLast24h(): int
    {
        $qb = $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.createdAt > :since')
            ->setParameter('since', new \DateTimeImmutable('-24 hours'));

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find jobs within a radius (km) of given latitude/longitude
     *
     * @param float $lat
     * @param float $lng
     * @param float $radiusKm
     * @return Job[]
     */
    public function findJobsWithinRadius(float $lat, float $lng, float $radiusKm): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT j.*
        FROM job j
        INNER JOIN job_geo_city jgc ON j.id = jgc.job_id
        INNER JOIN geo_city gc ON jgc.geo_city_id = gc.id
        WHERE (6371 * acos(
                   cos(radians(:lat)) *
                   cos(radians(gc.latitude)) *
                   cos(radians(gc.longitude) - radians(:lng)) +
                   sin(radians(:lat)) *
                   sin(radians(gc.latitude))
               )) <= :radius
    ';

        $stmt = $conn->prepare($sql);

        $stmt->bindValue('lat', $lat);
        $stmt->bindValue('lng', $lng);
        $stmt->bindValue('radius', $radiusKm);

        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();

        $jobIds = array_column($rows, 'id');
        if (empty($jobIds)) {
            return [];
        }

        // Als Entities laden
        return $this->findBy(['id' => $jobIds]);
    }
}
