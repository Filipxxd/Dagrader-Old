<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

class Criterias
{
    public int $teacherId;

    public function __construct(private \Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function setTeacherId(int $id)
    {
        $this->teacherId = $id;
    }


    public function getActivities(): ?array
    {
        return $this->database->fetchPairs('SELECT activityId, activityName FROM activities');
    }

    public function getAllActivities(): ?array
    {
        return $this->database->fetchAll('SELECT * FROM activities');
    }

    public function getActivityUnit($id): ?string
    {
        return $this->database->fetch('SELECT activityUnit FROM activities WHERE activityId = ?', $id)['activityUnit'];
    }

    public function getYears(): array
    {
        return array(
            1 => "První", 2  => "Druhý", 3  => "Třetí", 4  => "Čtvrtý",
        );
    }


    public function getCriterias(): ?array
    {
        return $this->database->query(
            '
			SELECT *
			FROM criterias
			WHERE teacherId = ?
',
            $this->teacherId
        )->fetchAll();
    }

    public function getCriteriasForYear(int $schoolYear): ?array
    {
        return $this->database->query(
            '
			SELECT c.*, a.activityName, a.activityUnit
			FROM criterias c
            INNER JOIN activities a
            ON c.activityId = a.activityId
			WHERE c.teacherId = ? AND schoolYear = ?',
            $this->teacherId,
            $schoolYear
        )->fetchAll();
    }


    public function getCriteriaInfo(int $criteriaId)
    {

        return $this->database->query(
            '
			SELECT a.activityName, c.schoolYear, c.teacherId 
			FROM criterias c
            JOIN activities a
            ON c.activityId = a.activityId
			WHERE c.Id = ? 
            ',
            $criteriaId

        )->fetch();
    }

    public function getCriteriaById(int $criteriaId)
    {
        return $this->database->query(
            "
			SELECT maleFirst, maleSecond, maleThird, maleFourth, femaleFirst, femaleSecond, femaleThird, femaleFourth, a.activityName
			FROM criterias c
            JOIN activities a
            ON c.activityId = a.activityId
			WHERE Id = ? 
            ",
            $criteriaId
        )->fetch();
    }

    public function getSpecificCriteria($year, int $activityId, int $gender)
    {
        $string = "maleFirst as first, maleSecond as second, maleThird as third, maleFourth as fourth";
        if ($gender === 1) $string = "femaleFirst as first, femaleSecond as second, femaleThird as third, femaleFourth as fourth";


        return $this->database->query(
            "
			SELECT $string
			FROM criterias
			WHERE teacherId = ? 
            AND schoolYear = ?
            AND activityId = ?
            ",
            $this->teacherId,
            $year,
            $activityId
        )->fetch();
    }

    public function setCriterias(float $male1, float $male2, float $male3, float $male4, float $female1, float $female2, float $female3, float $female4, $year, int $activityId, int $teacherId): void
    {
        $this->database->query("INSERT INTO criterias(maleFirst, maleSecond, maleThird, maleFourth, femaleFirst, femaleSecond, femaleThird, femaleFourth, schoolYear, activityId, teacherId)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $male1, $male2, $male3, $male4, $female1, $female2, $female3, $female4, $year, $activityId, $teacherId);
    }

    public function getMarkFromPerformance($databaseCriterias, $values): ?int
    {
        if ($databaseCriterias['first'] > $databaseCriterias['second']) {
            if ($databaseCriterias['first'] <= $values->performance) {
                return 1;
            } elseif ($databaseCriterias['second'] <= $values->performance) {
                return 2;
            } elseif ($databaseCriterias['third'] <= $values->performance) {
                return 3;
            } elseif ($databaseCriterias['fourth'] <= $values->performance) {
                return 4;
            } else {
                return 5;
            }
        } else {
            if ($databaseCriterias['first'] >= $values->performance) {
                return 1;
            } elseif ($databaseCriterias['second'] >= $values->performance) {
                return 2;
            } elseif ($databaseCriterias['third'] >= $values->performance) {
                return 3;
            } elseif ($databaseCriterias['fourth'] >= $values->performance) {
                return 4;
            } else {
                return 5;
            }
        }
    }
}