<?php

declare(strict_types=1);

namespace App\Presenters;


use Nette;
use Nette\Application\UI\Form;
use Nette\Http\Request;

final class ClassesPresenter extends BasePresenter
{
    protected $httpRequest;
    public function __construct(private \Nette\Database\Explorer $database,  Nette\Http\Request $httpRequest)
    {
        $this->database = $database;
        $this->httpRequest = $httpRequest;
    }

    final public function startup()
    {
        parent::startup();
        if ($this->getUser()->roles['role'] !== 1) {
            $this->redirect('Homepage:');
            die();
        }
        $this->template->classes = $this->database
            ->table('classes')
            ->select('classId, CONCAT(schoolYear, ".", classSymbol) AS classIdentifier')
            ->where('teacherId = ?', $this->getUser()->id)
            ->fetchAll();
    }

    public function handleRemoveStudent($userId): void
    {
        $this->database->query('UPDATE users SET classId = 0 WHERE userId = ?', $userId);
        $this->redirect("this");
    }


    protected function createComponentNewClass()
    {
        $form = new Form();
        $form->addProtection();
        $form->addSelect("schoolYear", "Vyberte ročník:", array(1 => "1.", 2 => "2.", 3 => "3.", 4 => "4."))
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Ročník")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer")
            ->setHtmlAttribute("ID", "schoolYear");

        $form->addSelect("classSymbol", "Vyberte třídu:", array("A" => "A", "B" => "B", "C" => "C", "D" => "D", "M" => "M", "P" => "P", "S" => "S"))
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Třída")
            ->setHtmlAttribute("class", "form-control mb-2 text-center pointer")
            ->setHtmlAttribute("ID", "classSymbol");

        $form->addSubmit("submit", "Přihlásit se")
            ->setHtmlAttribute("class", "btn btn-primary w-100")
            ->setHtmlAttribute("style", "border-radius: 10px;")
            ->setOmitted();

        $form->onSuccess[] = [$this, "newClassSuccess"];

        return $form;
    }
    public function newClassSuccess($form, $values)
    {
        $values = $form->getValues();

        $count = $this->database
            ->table('classes')
            ->where('teacherId = ? AND classSymbol = ? AND schoolYear = ?', $this->getUser()->id, $values->classSymbol, $values->schoolYear)
            ->count();

        if ($count > 0) {
            $this->flashMessage("Třída již existuje", 'alert-danger');
        } else {
            $this->database->table('classes')->insert([
                'teacherId' => $this->getUser()->id,
                'classSymbol' => $values->classSymbol,
                'schoolYear' => $values->schoolYear,
            ]);

            if ($this->httpRequest->getPost('userIds')) {
                foreach ($this->httpRequest->getPost('userIds') as $student => $studentId) {
                    $this->database->query("UPDATE users u INNER JOIN classes c ON teacherId = ? AND classSymbol = ? AND schoolYear = ? SET u.classId = c.classId WHERE u.userId = ? AND u.classId = 0 AND u.role = 0", $this->getUser()->id, $values->classSymbol, $values->schoolYear, (int) $studentId);
                }
            }

            $this->flashMessage("Třída založena!", 'alert-success');
            $this->redirect('Classes:');
        }
    }

    // Editace stavajici tridy
    protected function createComponentEditClass()
    {
        $form = new Form();
        $form->addProtection();
        $form->onSuccess[] = [$this, "editClassSuccess"];

        return $form;
    }
    public function editClassSuccess()
    {
        if ($this->httpRequest->getPost('userIds')) {
            foreach ($this->httpRequest->getPost('userIds') as $student => $studentId) {
                $this->database->query("UPDATE users u SET u.classId = ? WHERE u.userId = ? AND u.classId = 0 AND u.role = 0", $this->template->classId, $studentId);
            }
            $this->redirect('this');
        }
    }


    public function handleSearch($string): void
    {
        $this->setLayout('blank');
        $students = $this->database->query('SELECT userId, fName, lName FROM users WHERE (fName LIKE ? OR lName LIKE ?) AND classId = 0 AND role = 0 ', '%' . $string . '%', '%' . $string . '%')->fetchAll();
        $this->template->students = $students;
    }


    public function handleExportExcel($classId)
    {
        $this->setLayout('blank');
        $excelData = "";
        $fileName = "znamky - " . $this->template->class->className . ".csv";

        // $fields = array('ID', 'FIRST NAME', 'LAST NAME', 'EMAIL', 'GENDER', 'COUNTRY', 'CREATED', 'STATUS');
        // $excelData .= implode("\t", array_values($fields)) . "\n";

        $students = $this->database->query('SELECT userId, concat(u.fName, " ", u.lName) as fullname FROM users u WHERE u.classId = ?', $classId)->fetchAll();
        if (count($students) > 0) {
            foreach ($students as $student) {
                $excelRow = array($student->fullname);
                $marks = $this->database->query('SELECT mark FROM performances WHERE studentId = ?', $student->userId)->fetchAll();

                foreach ($marks as $mark) {
                    $excelRow[] = $mark->mark;
                }
                array_walk($excelRow, function (&$str) {
                    if (!is_int($str)) {
                        $str = preg_replace("/\t/", "\\t", $str);
                        $str = preg_replace("/\r?\n/", "\\n", $str);
                        if (strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
                    }
                });
                $excelData .= implode("\t", $excelRow) . "\n";
            }
        } else {
            $this->error("xd", 500);
            $this->redirect('this');
        }

        $excelData = mb_convert_encoding($excelData, 'UTF-16LE', 'UTF-8');
        //ob_clean();
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Date: ' . date('D M j G:i:s T Y'));
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . strlen($excelData));

        echo chr(255) . chr(254) . $excelData;
        die();
    }


    public function actionClass($classId)
    {
        $this->template->class = $this->database->query('SELECT concat(schoolYear, ".", classSymbol) as className FROM classes WHERE classId = ?', $classId)->fetchAll()[0];
        $this->template->students = $this->database->query('SELECT userId, fName, lName FROM users u WHERE u.classId = ? ORDER BY lName ASC', $classId)->fetchAll();
        $this->template->classId = $classId;
    }

    public function handleRemoveClass($classId)
    {
        $this->database->query('DELETE from classes WHERE teacherId = ? AND classId = ?', $this->getUser()->id, $classId);

        $classStudents = $this->database->query('SELECT userId from users WHERE classId = ?', $classId)->fetchAll();
        if (count($classStudents) > 0) {
            foreach ($classStudents as $student) {
                $this->database->query('DELETE from performances WHERE studentId = ?', $student->userId);
                $this->database->query('UPDATE users SET classId = 0 WHERE userId = ?', $student->userId);
            }
        }
        $this->flashMessage("Třída smazána", 'alert-success');
        $this->redirect('Classes:');
    }


    // STUDENT - VSECHNY VYKONY
    public function handlePerformances($userId)
    {
        $this->template->studentsPerf = $this->database->query('SELECT a.activityName, concat(p.performance, a.activityUnit) as performance, p.timedateAdd, p.mark FROM performances p JOIN activities a on p.activityId = a.activityId WHERE studentId = ? ORDER BY p.timedateAdd DESC', $userId)->fetchAll();
        $this->template->studentInfo = $this->database->query('SELECT fName, lName FROM users WHERE userId = ?', $userId)->fetch();
    }
}