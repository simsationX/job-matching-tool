<?php

namespace App\Service;

use App\Entity\Candidate;

class CandidateKeywordService
{
    private const CANDIDATE_FIELDS = ['position', 'industry', 'skills', 'additionalActivityAreas'];

    private const STOP_WORDS = ['und', 'oder', 'an', 'am', 'der', 'die', 'das', 'in', 'mit', 'von', 'für', 'auf', 'bei', 'zu', 'als'];

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

    public function extractKeywords(?string $text, array $existing = []): array
    {
        if (null === $text) {
            return $existing;
        }

        $tokens = preg_split('/[\s,;\/]+/u', $text);
        $filteredTokens = array_filter($tokens, static function(string $token): string {
            $stopWords = self::STOP_WORDS;
            $token = mb_strtolower(trim($token, " \t\n\r\0\x0B()"));
            return $token !== '' && !in_array($token, $stopWords, true);
        });

        return array_merge($existing, $filteredTokens);
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

    public function containsAnyKeyword(string $text, array $keywords): bool
    {
        $text = mb_strtolower($text);

        foreach ($keywords as $kw) {
            $kw = mb_strtolower(trim($kw));
            if ($kw !== '' && preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $text)) {
                return true;
            }
        }

        return false;
    }
}
