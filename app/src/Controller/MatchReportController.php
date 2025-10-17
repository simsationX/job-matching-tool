<?php

namespace App\Controller;

use App\Repository\CandidateJobMatchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class MatchReportController extends AbstractController
{
    public function __construct(private CandidateJobMatchRepository $candidateJobMatchRepository)
    {
    }

    #[Route('/admin/match-report/download', name: 'admin_match_report_download')]
    public function download(): StreamedResponse
    {
        $matches = $this->candidateJobMatchRepository->findAllForExport();

        $filename = 'candidate_job_matches_' . date('Y-m-d_His') . '.csv';

        $response = new StreamedResponse(function() use ($matches) {
            echo "\xEF\xBB\xBF"; // BOM für Excel/UTF-8

            $out = fopen('php://output', 'wb');

            fputcsv($out, [
                'candidate_id',
                'candidate_name',
                'candidate_location',
                'candidate_additional_locations',
                'job_company',
                'job_position',
                'job_description',
                'job_location',
                'score',
                'found_at'
            ]);

            foreach ($matches as $row) {
                $row['score'] = number_format((float)$row['score'], 2, '.', '');
                $row['found_at'] = $row['found_at'] ? $row['found_at']->format('Y-m-d H:i:s') : '';
                fputcsv($out, $row);
            }

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response;
    }
}
