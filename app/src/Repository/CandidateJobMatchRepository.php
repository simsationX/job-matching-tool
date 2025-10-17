<?php

namespace App\Repository;

use App\Entity\CandidateJobMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CandidateJobMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidateJobMatch::class);
    }

    public function findAllBeforeExport(?\DateTimeInterface $lastExportedAt = null): array
    {
        $qb = $this->createQueryBuilder('cjm')
            ->select(
                '
                c.id AS candidate_id,
                c.name AS candidate_name,
                c.location AS candidate_location,
                c.additionalLocations AS candidate_additional_locations,
                consultant.name AS candidate_consultant,
                cjm.company AS job_company,
                cjm.website AS job_website,
                cjm.position AS job_position,
                cjm.description AS job_description,
                cjm.location AS job_location,
                cjm.score AS score,
                cjm.foundAt AS found_at
            '
            )
            ->join('cjm.candidate', 'c')
            ->leftJoin('c.consultant', 'consultant')
            ->andWhere('cjm.status = :activeStatus')
            ->setParameter('activeStatus', 'active')
            ->orderBy('c.id', 'ASC')
            ->addOrderBy('cjm.score', 'DESC');

        if ($lastExportedAt) {
            $qb->andWhere('cjm.foundAt > :lastExportedAt')
                ->setParameter('lastExportedAt', $lastExportedAt);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
