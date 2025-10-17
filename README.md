# Symfony 7 Job-Matching MVP - Docker Setup

This project is a Symfony 7 MVP for job matching, with a Docker setup including PHP-FPM, MySQL, Nginx, and Mailhog.

---

## 1. Prerequisites for local development

- Docker >= 24.x
- Docker Compose >= 2.x

---

## 2. Setup

### 2.1 Build containers

```bash
docker compose build
docker compose up -d
```

Services:

PHP-FPM → http://localhost:8000
Nginx → http://localhost:8080
Mailhog Web UI → http://localhost:8025
Adminer (optional) → http://localhost:8081

### 2.2 Installing dependencies

Enter PHP container

```bash
docker exec -it webserver bash
```

Inside container:
```bash
composer install
```

### 2.3 Database setup

Inside container:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 2.4 Stopping container

```bash
docker compose down
docker compose logs -f php
```


### 3. Setup data

1) Import Geo Cities

```bash
bin/console app:geo-city-import data/zipcodes.de.csv
```

2) Import MySQL snippets in /data to populate activity_area and industry database.

### 4. Workflow

1) Login dashboard
2) Upload candidates.csv. All entries in candidates.csv will be added as new candidates.
3) Upload jobs.xlsx. The file will be uploaded to /uploads directory.
3.1) The cronjob `bin/console app:jobs-import` runs every 30mins. The cronjob deletes always all jobs and creates them new.
4) The cronjob `bin/console app:embed-job` runs once per night. The cronjobs checks for new created jobs in the last 24hrs.
If so, it creates a new FAISS model for the fields `position` and `description`. This takes an hour for 60000 jobs.
FAISS will be used for semantic search
5) The cronjob `bin/console app:candidate-job-match new` will run once a night. Or can be started from candidate job match dashboard.
The command will calculcate job matches for candidates having no job matches yet. If new matches will be found an email with CSV with new job matches will be sent.
By `bin/console app:candidate-job-match all` you can calculate all job matches again.
6) If you delete a job match in the dashboard, the status will be set to ignored.
So it won't show in the list view anymore, but it is still in the database, so that the candidate can not be matched with this job again.
7) If you change a job after the match and you run the `bin/console app:candidate-job-match all` the matches will be recalculated.
8) If you change candidates having matches, you have to run `bin/console app:candidate-job-match all` to recalculate all matches.
If you want to calculate matches of one specific candidate (in the case you change the candidate), then you have to delete all his matches by hand 
and run `bin/console app:candidate-job-match new`. Or you can delete the candidate and create the candidate new and run `bin/console app:candidate-job-match new`.

### 5. Matching of eolocation

When importing jobs or creating/updating jobs in the dashboard, the location of the job will be matched with the geo location.

The matching logic works with these formats:

Berlin
38126 Braunschweig
63110 Rodgau Hessen
Hybrides Arbeiten in 76767 Hagenbach
Hybrides Arbeiten in Berlin
10179 Berlin Berlin
Darmstadt, Hesse, Germany
Darmstadt, München, Frankfurt
45481 Mülheim an der Ruhr Nordrhein-Westfalen
Mülheim an der Ruhr Nordrhein-Westfalen
Duisburg, Dortmund, Unna, Oberhausen, Essen, Bottrop, Mülheim, Herne, Bochum, Ennepe-Ruhr-Kreis, Hamm, Wesel, Gelsenkirchen, Hagen, Recklinghausen
Düsseldorf, Nordrhein-Westfalen | Dresden, Sachsen | Bad Hersfeld, Hessen
Deutschland
Germany
Allemagne

The matching algorithm splits all "," or "|" in the location field.
For each split it will lookup for the geolocation.
PLZ is always prioritized. If PLZ is found in location, PLZ will be used for lookup.
If not up to 4 words are used to match the geolocation by place. If multiple places have the same name, the first one will be used.

Following entries won't work:

Hybrides Arbeiten in Nordrhein-Westfalen
Bayern
Thüringen
Hybrides Arbeiten in Bayern
Santiago de Querétaro
Tyskland
Frankfurt

Bundesländer can't be resolved.
Frankfurt should be "Frankfurt am Main" or "Frankfurt (Oder)".

### 5. Job matching logic

The job matching logic works like this:

1) Radius search (50 km) determines the initial set of jobs.
1a) All Germany-wide jobs are added to this set.
2) The candidate's keywords from position, industry, additional industry (dropdown), activity areas (dropdown), additional activity areas, skills, and location are searched within each job’s position and job description from this set.
3a) Exact (1:1) matches yield a higher score per keyword.
3b) Partial (LIKE) matches yield a lower score per keyword.
4) The candidate’s keywords from position and industry are semantically compared with each job’s position and description → lowest weight in scoring.
