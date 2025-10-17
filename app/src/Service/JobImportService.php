<?php

namespace App\Service;

use App\Entity\Job;
use App\Entity\JobImportError;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Security\Http\LoginLink\Exception\ExpiredLoginLinkException;

class JobImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private JobLocationResolverService $jobLocationResolverService,
    ) {}

    public function import(string $filePath): int
    {
        ini_set('memory_limit', '-1');

        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0'); // nötig, falls FK bestehen
        $connection->executeStatement($platform->getTruncateTableSQL('job', true));
        $connection->executeStatement($platform->getTruncateTableSQL('job_import_error', true));
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true); // nur Werte laden
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $batchSize = 1000;
        $imported = 0;
        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex++;

            if ($rowIndex < 6) {
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $cell->getValue();
            }

            $fieldNames = [
                'company',
                'companyPhone',
                'website',
                'contactPerson',
                'contactEmail',
                'contactPhone',
                'position',
                'positionId',
                'adId',
                'location',
                'description',
            ];

            $fields = array_combine($fieldNames, array_pad($values, count($fieldNames), null));

            $required = ['company', 'position', 'positionId', 'adId', 'description'];
            $missingFields = array_filter($required, static fn($f) => empty($fields[$f]));

            if (!empty($missingFields)) {
                $this->createJobImportError(
                    $fields,
                    $missingFields
                );
                continue;
            }

            $job = new Job();
            $job->setCompany($fields['company']);
            $job->setCompanyPhone($fields['companyPhone']);
            $job->setWebsite($fields['website']);
            $job->setContactPerson($fields['contactPerson']);
            $job->setContactEmail($fields['contactEmail']);
            $job->setContactPhone($fields['contactPhone']);
            $job->setPosition($fields['position']);
            $job->setPositionId((int)$fields['positionId']);
            $job->setAdId((int)$fields['adId']);
            $job->setLocation($fields['location']);
            $job->setDescription($fields['description']);

            $geoCities = [];
            $location = $fields['location'];

            if ($this->jobLocationResolverService->shouldResolveLocation($location)) {
                $geoCities = $this->jobLocationResolverService->resolve($location);
            }

            foreach ($geoCities as $geoCity) {
                if (!$job->getGeoCities()->contains($geoCity)) {
                    $job->addGeoCity($geoCity);
                }
            }

            $this->em->persist($job);
            $imported++;

            if ($imported % $batchSize === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();
        $this->em->clear();

        return $imported;
    }

    private function createJobImportError(array $values, array $missingFields = []): void
    {
        $errorMessages = [];

        if (!empty($missingFields)) {
            $errorMessages[] = implode(', ', array_map(
                static fn(string $f) => "$f is missing",
                $missingFields
            ));
        }

        $error = new JobImportError();
        $error->setCompany($values['company'] ?? '')
            ->setCompanyPhone($values['companyPhone'] ?? null)
            ->setWebsite($values['website'] ?? null)
            ->setContactPerson($values['contactPerson'] ?? null)
            ->setContactEmail($values['contactEmail'] ?? null)
            ->setContactPhone($values['contactPhone'] ?? null)
            ->setPosition($values['position'] ?? '')
            ->setPositionId((int)($values['positionId'] ?? 0))
            ->setAdId((int)($values['adId'] ?? 0))
            ->setLocation($values['location'] ?? null)
            ->setDescription($values['description'] ?? null)
            ->setError(implode(' | ', $errorMessages));

        $this->em->persist($error);
    }
}
