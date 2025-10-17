<?php

namespace App\Twig;

use App\Entity\Candidate;
use App\Service\CandidateKeywordService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class HighlightExtension extends AbstractExtension
{
    private CandidateKeywordService $keywordService;

    public function __construct(CandidateKeywordService $keywordService)
    {
        $this->keywordService = $keywordService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('candidate_keywords', [$this, 'getCandidateKeywords']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight_keywords', [$this, 'highlightKeywords'], ['is_safe' => ['html']]),
        ];
    }

    public function getCandidateKeywords(Candidate $candidate): array
    {
        return $this->keywordService->extractCandidateKeywords($candidate);
    }

    public function highlightKeywords(string $text, array $keywords): string
    {
        return $this->keywordService->highlightText($text, $keywords);
    }
}
