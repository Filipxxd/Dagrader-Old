<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\Criterias;
use Nette;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

final class CriteriasPresenter extends BasePresenter
{
    private Criterias $criterias;

    final public function __construct(private \Nette\Database\Explorer $database, Criterias $criterias)
    {
        $this->database = $database;
        $this->criterias = $criterias;
    }


    final public function startup()
    {
        parent::startup();

        if ($this->getUser()->roles['role'] === 1) { // UCITEL
            $this->criterias->setTeacherId($this->getUser()->id);
            $activities = $this->criterias->getAllActivities();
            $teacherC = array();
            foreach ($activities as $activityId => $activity) {
                $teacherC[$activity->activityName] = $this->database->query('SELECT c.* FROM criterias c WHERE teacherId = ? AND activityId = ? ORDER BY schoolYear ASC', $this->getUser()->id, $activity->activityId)->fetchAll();
            }
            $this->template->activities = $activities;
            $this->template->criteriasT = $teacherC;
        } else { // STUDENT
            $criteriaInfo = $this->database->query('SELECT teacherId, schoolYear FROM classes c INNER JOIN users u ON c.classId = u.classId WHERE u.userId = ?', $this->getUser()->id)->fetch();
            if ($criteriaInfo === null) {
                $this->flashMessage("Nejste přiřazeni do žádné třídy. Konaktuje vašeho učitele.", 'alert-danger');
                $this->redirect('Homepage:');
                die();
            }
            $this->criterias->setTeacherId($criteriaInfo['teacherId']);
            $this->template->gender = $this->getUser()->getIdentity()->data['gender'];
            $this->template->criteriasS = $this->criterias->getCriteriasForYear($criteriaInfo['schoolYear']);
        }
    }

    public function actionEditCriteria($criteriaId)
    {
        if ($this->getUser()->roles['role'] === 1) {
            $criterias = $this->criterias->getCriteriaInfo((int)$criteriaId);
            if ($criterias) {
                if ($criterias->teacherId === $this->getUser()->id) {
                    $this->template->activityName = $criterias->activityName;
                    $this->template->criteriaId = $criteriaId;
                    $this->template->schoolYear = $criterias->schoolYear;
                } else {
                    $this->error("ID učitele a ID tvůrce kritéria se neshoduje", 403);
                    die();
                }
            } else {
                $this->error("Kritérium s ID " . $criteriaId . " neexistuje", 404);
                die();
            }
        } else {
            $this->error("Uživatel není učitel - role není 1", 403);
            die();
        }
    }
    protected function createComponentEditForm(): Form
    {
        $criterias = $this->criterias->getCriteriaById((int)$_GET['criteriaId']);
        $form = new Form();
        $form->addProtection();
        $form->addText('maleFirst', 'Kritérium pro známku výborně')
            ->setHtmlAttribute("placeholder", $criterias->maleFirst)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('maleSecond', 'Kritérium pro známku chvalitebně')
            ->setHtmlAttribute("placeholder",  $criterias->maleSecond)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('maleThird', 'Kritérium pro známku dobře')
            ->setHtmlAttribute("placeholder",  $criterias->maleThird)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('maleFourth', 'Kritérium pro známku dostatečně')
            ->setHtmlAttribute("placeholder",  $criterias->maleFourth)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleFirst', 'Kritérium pro známku výborně')
            ->setHtmlAttribute("placeholder",  $criterias->femaleFirst)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleSecond', 'Kritérium pro známku chvalitebně')
            ->setHtmlAttribute("placeholder",  $criterias->femaleSecond)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleThird', 'Kritérium pro známku dobře')
            ->setHtmlAttribute("placeholder",  $criterias->femaleThird)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleFourth', 'Kritérium pro známku dostatečně')
            ->setHtmlAttribute("placeholder",  $criterias->femaleFourth)
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);
        $form->onSuccess[] = [$this, "editSuccess"];

        return $form;
    }


    public function editSuccess($form, $values)
    {

        $values = $form->getValues();

        $count = $this->database
            ->table('criterias')
            ->where('teacherId = ? AND Id = ?', $this->getUser()->id, $this->template->criteriaId)
            ->count();
        if ($count === 0) {
            $this->flashMessage("Nastala neočekávaná chyba, opakujte akci později", 'alert-danger');
            $this->redirect('Criterias:');
            die();
        } else {
            $nullCount = 0;
            foreach ($values as $name => $value) {
                if ($value) {
                    $this->database->query('UPDATE criterias SET ' . $name . ' = ? WHERE id = ?', $value, $this->template->criteriaId);
                } else $nullCount += 1;
            }
            if ($nullCount !== $values->count()) {
                $this->flashMessage('Kritérium uspěšně upraveno!', 'alert-success');
                $this->redirect('Criterias:');
                die();
            } else {
                $this->flashMessage('Je nutno vyplnit alespoň jedno pole!', 'alert-warning');
                $this->redirect('this');
                die();
            }
        }
    }


    public function createComponentCriteriasForm(): Form
    {
        $form = new Form();
        $form->addProtection();
        $form->addSelect("activity", "Vyberte druh aktivity:", $this->criterias->getActivities())
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Aktivita")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer");

        $form->addSelect("year", "Vyberte ročník:", $this->criterias->getYears())
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Ročník")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer");

        $form->addText('maleFirst', 'Kritérium pro známku výborně')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('maleSecond', 'Kritérium pro známku chvalitebně')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('maleThird', 'Kritérium pro známku dobře')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('maleFourth', 'Kritérium pro známku dostatečně')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleFirst', 'Kritérium pro známku výborně')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleSecond', 'Kritérium pro známku chvalitebně')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addtext('femaleThird', 'Kritérium pro známku dobře')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT, "iksdeckobro")
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->addText('femaleFourth', 'Kritérium pro známku dostatečně')
            ->setHtmlAttribute("placeholder", "Kritérium")
            ->setHtmlAttribute("class", "form-control w-50 ms-auto text-center")
            ->setRequired()
            ->addRule($form::FLOAT)
            ->addRule($form::MIN, "Minimální platná hodnota je %d", 0)
            ->addRule($form::MAX, "Maximální platná hodnota je %d", 10000);

        $form->onSuccess[] = [$this, "addActivitySuccess"];

        return $form;
    }

    public function addActivitySuccess($form, $values)
    {

        $values = $form->getValues();

        $count = $this->database
            ->table('criterias')
            ->where('teacherId = ? AND schoolYear = ? AND activityId = ?', $this->getUser()->id, $values->year, $values->activity)
            ->count();

        if ($count > 0) {
            $this->flashMessage("Aktivita již existuje", 'alert-danger');
            $this->redirect('this');
            die();
        } else {
            $this->criterias->setCriterias(
                $values->maleFirst,
                $values->maleSecond,
                $values->maleThird,
                $values->maleFourth,
                $values->femaleFirst,
                $values->femaleSecond,
                $values->femaleThird,
                $values->femaleFourth,
                $values->year,
                $values->activity,
                $this->getUser()->id
            );
            $this->flashMessage("Aktivita úspěšně přidána!", 'alert-success');
            $this->redirect('Criterias:');
            die();
        }
    }
}