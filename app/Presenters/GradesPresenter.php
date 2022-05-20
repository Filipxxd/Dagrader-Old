<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use App\Model\Criterias;
use Nette\Application\UI\Form;

final class GradesPresenter extends BasePresenter
{
    private Criterias $criterias;
    private int $schoolYear;

    public function __construct(Criterias $criterias, private \Nette\Database\Explorer $database)
    {
        $this->criterias = $criterias;
        $this->database = $database;
    }

    final public function startup()
    {
        parent::startup();

        // Check role
        if ($this->getUser()->roles['role'] === 0) {
            $studentIdentification = $this->database->query('SELECT teacherId, schoolYear FROM classes c INNER JOIN users u ON c.classId = u.classId WHERE u.userId = ?', $this->getUser()->id)->fetch();
            if ($studentIdentification === null) {
                $this->flashMessage("Nejste přiřazeni do žádné třídy, kontaktujte svého učitele.", 'alert-danger');
                $this->redirect('Homepage:');
                die();
            }
            $this->schoolYear = $studentIdentification->schoolYear;
            $this->criterias->setTeacherId($studentIdentification["teacherId"]);
        } else {
            $this->error("Uživatel je učitel - nemá přístup ke známkám", 403);
            die();
        }

        // Vypsani znamek studenta
        $performances = array();
        $res = $this->database->query("SELECT * FROM performances WHERE studentId = ? ORDER BY timedateAdd DESC", $this->getUser()->id);
        foreach ($res as $databasePerformance) {
            $performances[$databasePerformance->performanceId] = array(
                'date' => $databasePerformance->timedateAdd->format('d/m/Y'),
                'activity' => $this->criterias->getActivities()[$databasePerformance->activityId],
                'performance' => ($databasePerformance->performance .  $this->criterias->getActivityUnit($databasePerformance->activityId)),
                'mark' => $databasePerformance->mark === 0 ? "Kriterium nenalezeno" : $databasePerformance->mark,
            );
        }
        $this->template->performances = $performances;
    }


    protected function createComponentPerformanceAdd(): Form
    {
        $form = new Form();

        $form->addSelect("activityId", "Vyberte druh aktivity:", $this->criterias->getActivities())
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Aktivita")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer");

        $form->addText('performance', 'První číslo:')
            ->setRequired()
            ->setHtmlAttribute("class", "form-control text-center")
            ->setHtmlAttribute("ID", "performanceChoice")
            ->AddRule(Form::FLOAT, "ERROR NO FLOAT");


        $form->addSubmit("submit", "Odeslat")
            ->setHtmlAttribute("class", "pointer m-0 p-0 ms-auto mt-3 fas fa-paper-plane")
            ->setHtmlAttribute("style", "background: none; border: 0; font-size: 2rem;")
            ->setOmitted();

        $form->onSuccess[] = [$this, "performanceSuccess"];
        return $form;
    }
    public function performanceSuccess($form, $values)
    {
        // Kontrola existence kriterii
        $databaseCriterias = $this->criterias->getSpecificCriteria($this->schoolYear, $values->activityId, $this->getUser()->getIdentity()->data['gender']);
        if ($databaseCriterias) {

            // Kontrola max 3 zaznamy denne
            $thirdPerformance = $this->database->query('SELECT timedateAdd FROM performances WHERE studentId = ? ORDER BY timedateAdd DESC LIMIT 2,1', $this->getUser()->id)->fetch();
            if (!isset($thirdPerformance)) {
                $thirdPerformance = date('Y-m-d', strtotime('today - 1 days'));
            } else $thirdPerformance = $thirdPerformance[0]->format('Y-m-d');
            if (date_diff(date_create(date('Y-m-d')), date_create($thirdPerformance))->days !== 0) {
                $values = $form->getValues();
                $mark = $this->criterias->getMarkFromPerformance($databaseCriterias, $values);
                $this->database->query("INSERT INTO performances(performance, mark, activityId, studentId) VALUES (?, ?, ?, ?)", number_format($values->performance, 2, "."), $mark, $values->activityId, $this->getUser()->id);
                $this->flashMessage("Výkon úspěšně přidán!", 'alert-success');
            } else {
                $this->flashMessage("Lze přidat maximálně 3 záznamy za den!", 'alert-danger');
            }
        } else {
            $this->flashMessage("Váš učitel prozatím nevytvořil kritéria pro tuto aktivitu.", 'alert-danger');
        }
        $this->redirect('this');
    }
}