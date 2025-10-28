<?php
namespace App\Service;

use App\Entity\GeoCity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

final class GeoCityImportService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function importCsv(string $csvPath, int $batchSize = 1000): void
    {
        if (!file_exists($csvPath)) {
            throw new \InvalidArgumentException("CSV file not found: $csvPath");
        }

        $rows = array_map('str_getcsv', file($csvPath));
        if (empty($rows)) {
            throw new \RuntimeException('CSV file is empty');
        }

        $header = array_shift($rows);
        $map = array_flip($header);

        $count = 0;

        foreach ($rows as $i => $row) {
            $geoCity = new GeoCity();
            $geoCity->setZipcode($row[$map['zipcode']] ?? '');
            $geoCity->setPlace($row[$map['place']] ?? '');
            $geoCity->setLatitude((float)($row[$map['latitude']] ?? 0));
            $geoCity->setLongitude((float)($row[$map['longitude']] ?? 0));

            $this->em->persist($geoCity);
            $count++;

            if ($count % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        // Letzter Flush für Rest
        $this->em->flush();
        $this->em->clear();
    }
}
