<?php

namespace App\Service;

use App\Entity\Candidate;

class CandidateKeywordService
{
    private const CANDIDATE_FIELDS = ['position', 'industry', 'skills', 'additionalActivityAreas'];

    public function __construct() {}

    /**
     * @return string[]
     */
    public function extractCandidateKeywords(Candidate $candidate): array
    {
        $keywords = [];

        foreach (self::CANDIDATE_FIELDS as $field) {
            $fieldValue = $candidate->{'get' . ucfirst($field)}();
            $keywords = $this->extractKeywords($fieldValue, $keywords);
        }

        foreach ($candidate->getAdditionalIndustries() as $industry) {
            $keywords = $this->extractKeywords($industry->getName(), $keywords);
        }

        foreach ($candidate->getActivityAreas() as $activityArea) {
            $keywords = $this->extractKeywords($activityArea->getName(), $keywords);
        }

        return array_unique(array_filter($keywords));
    }

    private function extractKeywords(?string $text, array $existing = []): array
    {
        if (null === $text) {
            return $existing;
        }

        $tokens = preg_split('/[\s,;\/]+/u', $text);
        return array_merge($existing, $tokens);
    }

    public function highlightText(string $text, array $keywords): string
    {
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);

            if ($keyword === '') {
                continue;
            }

            $escaped = preg_quote($keyword, '/');
            $text = preg_replace(
                '/\b(' . $escaped . ')\b/iu',
                '<mark>$1</mark>',
                $text
            );
        }

        return $text;
    }
}
