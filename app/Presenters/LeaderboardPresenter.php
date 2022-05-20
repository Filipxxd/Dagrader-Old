<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use App\Model\Criterias;
use Nette\Application\UI\Form;

final class LeaderboardPresenter extends BasePresenter
{
    private Criterias $criterias;

    final public function __construct(Criterias $criterias, private \Nette\Database\Explorer $database)
    {
        $this->database = $database;
        $this->criterias = $criterias;
    }
    final public function startup()
    {
        parent::startup();
    }


    protected function createComponentFilterForm(): Form
    {
        $form = new Form();

        $form->addSelect("yearSelector", "Vyberte ročník:", array(1 => "1.", 2 => "2.", 3 => "3.", 4 => "4."))
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Ročník")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer")
            ->setHtmlAttribute("ID", "yearSelector");

        $form->addSelect("activitySelector", "Vyberte aktivitu:", $this->criterias->getActivities())
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Aktivita")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer")
            ->setHtmlAttribute("ID", "activitySelector");

        $form->addSelect("genderSelector", "Vyberte pohlaví:", array(0 => 'Chlapci', 1 => 'Dívky'))
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Pohlaví")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer")
            ->setHtmlAttribute("ID", "genderSelector");

        return $form;
    }

    public function handleSearch($schoolYear, $activityId, $gender): void
    {
        $this->setLayout('blank');
        $crit = ($this->database->query('SELECT maleFirst, maleSecond FROM criterias WHERE schoolYear = ?  AND activityId = ?', $schoolYear, $activityId)->fetch());
        $performances = array();
        if (isset($crit)) {
            if ($crit->maleFirst > $crit->maleSecond) $option = array("DESC", "MAX"); // Pokud je kritérium pro známku výborně jednotkově větší než krit. pro chvalitebně
            else $option = array("ASC", "MIN");

            $performances = $this->database->query('SELECT concat(' . $option[1] . '(p.performance), a.activityUnit) as performance, concat(u.fName, " ", u.lName) as studentName FROM performances p INNER JOIN activities a ON a.activityId = p.activityId INNER JOIN users u ON p.studentId = u.userId AND u.gender = ? INNER JOIN classes c ON c.classId = u.classId WHERE c.schoolYear = ? AND p.activityId = ? GROUP BY p.studentId ORDER BY performance ' . $option[0] . ' LIMIT 10 ', $gender, $schoolYear, $activityId)->fetchAll();
        }
        $this->template->performances = $performances;
    }
}