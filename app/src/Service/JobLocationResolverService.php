<?php

namespace App\Service;

use App\Entity\GeoCity;
use App\Repository\GeoCityRepository;

class JobLocationResolverService
{
    private const IGNORED_LOCATIONS = [
        'deutschland',
        'germany',
        'allemagne',
        'bundesweit'
    ];

    private const LOCATION_MAP = [
        'munich'         => 'München',
        'nuremberg'      => 'Nürnberg',
        'cologne'        => 'Köln',
        'dusseldorf'     => 'Düsseldorf',
        'duesseldorf'    => 'Düsseldorf',
        'hanover'        => 'Hannover',
        'coblenz'        => 'Koblenz',
        'goettingen'     => 'Göttingen',
        'wuerzburg'      => 'Würzburg',
        'moenchengladbach' => 'Mönchengladbach',
        'brunswick'      => 'Braunschweig',
        'osnabrueck'     => 'Osnabrück',
    ];

    public function __construct(private GeoCityRepository $geoCityRepository) {}

    /**
     * @return GeoCity[]
     */
    public function resolve(string $location): array
    {
        $result = [];
        $fragments = preg_split('/[|,]/', $location);

        foreach ($fragments as $fragment) {
            $fragment = trim($fragment);

            if ($fragment === '') {
                continue;
            }

            $city = $this->resolveFragment($fragment);
            if (null !== $city) {
                $result[$city->getId()] = $city;
            }
        }

        return array_values($result);
    }

    private function resolveFragment(string $fragment): ?GeoCity
    {
        $fragment = trim($fragment);

        // PLZ check in fragment
        if (preg_match('/\b\d{5}\b/', $fragment, $matches)) {
            $postalCode = $matches[0];
            $geoCities = $this->geoCityRepository->findBy(['zipcode' => $postalCode]);

            if (!empty($geoCities)) {
                $afterPlz = trim(preg_replace('/.*' . preg_quote($postalCode, '/') . '\s*/', '', $fragment));
                $words = preg_split('/\s+/', $afterPlz);
                $wordCount = count($words);

                // multiple cities for same plz, sliding window on fragment to match "Berlin" in "Hybrides Arbeiten in Berlin"
                for ($len = min(4, $wordCount); $len >= 1; $len--) {
                    for ($i = 0; $i <= $wordCount - $len; $i++) {
                        $phrase = implode(' ', array_slice($words, $i, $len));
                        $phrase = $this->normalizeLocationPhrase($phrase);

                        foreach ($geoCities as $city) {
                            if (stripos($city->getPlace(), $phrase) !== false) {
                                return $city;
                            }
                        }
                    }
                }

                // Fallback: first geo city
                return $geoCities[0];
            }
        }

        // no PLZ, sliding window on fragment to match "Berlin" in "Hybrides Arbeiten in Berlin"
        $words = preg_split('/\s+/', $fragment);
        $wordCount = count($words);

        for ($len = min(4, $wordCount); $len >= 1; $len--) {
            for ($i = 0; $i <= $wordCount - $len; $i++) {
                $phrase = implode(' ', array_slice($words, $i, $len));
                $phrase = $this->normalizeLocationPhrase($phrase);

                $geoCities = $this->geoCityRepository->findBy(['place' => $phrase], null, 1);
                if (!empty($geoCities)) {
                    return $geoCities[0];
                }
            }
        }

        return null;
    }

    private function normalizeLocationPhrase(string $phrase): string
    {
        return self::LOCATION_MAP[mb_strtolower($phrase)] ?? $phrase;
    }

    public function shouldResolveLocation(?string $location): bool
    {
        if (null === $location) {
            return false;
        }

        return !in_array(mb_strtolower($location), self::IGNORED_LOCATIONS, true);
    }
}
