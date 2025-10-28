<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\CandidateJobMatch;
use App\Entity\Enum\CandidateJobMatchStatus;
use App\Repository\CandidateJobMatchRepository;
use App\Repository\CandidateRepository;
use App\Service\CandidateJobMatchMailerService;
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
        private CandidateRepository $candidateRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/admin/candidate/{id}/matches/bulk-delete', name: 'candidate_matches_bulk_delete', methods: ['POST'])]
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

    #[Route('/admin/candidate/{id}/match/{matchId}/send', name: 'candidate_match_mail_send', methods: ['POST'])]
    public function sendSingleMatch(
        int $id,
        int $matchId,
        Request $request,
        CandidateJobMatchMailerService $jobMatchMailer
    ): Response {
        /** @var Candidate $candidate */
        $candidate = $this->candidateRepository->find($id);
        if (null === $candidate) {
            $this->addFlash('danger', 'Kandidat nicht gefunden.');
            return $this->redirectToRoute('admin_dashboard');
        }

        /** @var CandidateJobMatch $match */
        $match = $this->candidateJobMatchRepository->find($matchId);
        if (null === $match) {
            $this->addFlash('danger', 'Match nicht gefunden.');
            return $this->redirectToRoute('candidate_detail', ['id' => $id]);
        }

        $mailText = $request->request->get('mailText');

        $results = $jobMatchMailer->sendMatch($candidate, $match, $mailText);

        $this->addFlash(
            $results['success'] ? 'success' : 'danger',
            $results['success'] ? 'Mail erfolgreich versendet.' : $results['error']
        );

        if (false === $results['success']) {
            $url = $this->adminUrlGenerator
                ->setController(CandidateCrudController::class)
                ->setAction('previewMail')
                ->setEntityId($id)
                ->set('matchId', $match->getId())
                ->generateUrl();

            return $this->redirect($url);
        }

        $url = $this->adminUrlGenerator
            ->setController(CandidateCrudController::class)
            ->setAction('detail')
            ->setEntityId($id)
            ->generateUrl();

        return $this->redirect($url);
    }
}
