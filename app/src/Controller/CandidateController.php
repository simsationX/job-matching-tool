<?php

namespace App\Controller;

use App\Entity\CandidateJobMatch;
use App\Entity\Enum\CandidateJobMatchStatus;
use App\Repository\CandidateJobMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CandidateController extends AbstractController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private CandidateJobMatchRepository $candidateJobMatchRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/admin/candidate/{id}/matches/bulk-ignore', name: 'candidate_matches_bulk_ignore', methods: ['POST'])]
    public function bulkIgnoreMatches(
        int $id,
        Request $request,
    ): Response {
        $matchIds = (array) $request->request->all('matches');

        if (!empty($matchIds)) {
            foreach ($matchIds as $matchId) {
                /** @var CandidateJobMatch $match */
                $match = $this->candidateJobMatchRepository->find($matchId);
                if ($match) {
                    $match->setStatus(CandidateJobMatchStatus::IGNORED);
                    $this->entityManager->persist($match);
                }
            }
            $this->entityManager->flush();

            if (1 === count($matchIds)) {
                $this->addFlash('success', '1 Match gelöscht.');
            } else {
                $this->addFlash('success', count($matchIds).' Matches gelöscht.');
            }
        } else {
            $this->addFlash('warning', 'Keine Matches ausgewählt.');
        }

        $url = $this->adminUrlGenerator
            ->setController(CandidateCrudController::class)
            ->setAction('detail')
            ->setEntityId($id)
            ->generateUrl();

        return $this->redirect($url);
    }
}
