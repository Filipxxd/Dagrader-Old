<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class UserPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;
    private Nette\Http\Session $session;

    public function __construct(Nette\Database\Explorer $database, Nette\Http\Session $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    public function startup()
    {
        parent::startup();
        $this->session->start();
    }

    //SIGNOUT
    public function actionSignOut()
    {
        $this->getUser()->logout(true);
        $this->flashMessage("Úspěšně odhlášen!", 'alert-success');
        $this->redirect('User:login');
    }

    public function beforeRender()
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect("Homepage:");
            die();
        }
    }

    // LOGIN
    protected function createComponentSignInForm()
    {
        $form = new Form();
        $form->addProtection();

        $form->addText("login")
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Uživatelské jméno")
            ->setHtmlAttribute("class", "form-control")
            ->addRule($form::MAX_LENGTH, 'Uživatelské jméno nesmí být delší než %d znaků', 15);

        $form->addPassword("password")
            ->setRequired()
            ->setHtmlAttribute("placeholder", 'Heslo')
            ->setHtmlAttribute("class", "form-control pr-0")
            ->addRule($form::MAX_LENGTH, 'Heslo nesmí být delší než %d znaků', 25);

        $form->addCheckbox("stayLoggedIn", "Zůstat přihlášen")
            ->setHtmlAttribute("class", "form-check-input pointer");

        $form->addSubmit("submit", "Přihlásit se")
            ->setHtmlAttribute("class", "btn btn-primary w-100")
            ->setHtmlAttribute("style", "border-radius: 10px;");

        $form->onSuccess[] = [$this, "signInSuccess"];

        return $form;
    }

    public function signInSuccess($form, $values)
    {
        $values = $form->getValues();

        try {
            $this->getUser()->login($values->login, $values->password);
        } catch (Nette\Security\AuthenticationException $e) {
            $this->flashMessage("Chybné uživatelské jméno nebo heslo", 'alert-danger');
            $this->redirect('login');
        }

        if ($values->stayLoggedIn) {
            $this->getUser()->setExpiration('14 days');
        } else $this->getUser()->setExpiration('15 minutes');


        $this->redirect('Homepage:default');
        die();
    }



    //REGISTER
    protected function createComponentRegisterForm()
    {
        $form = new Form();
        $form->addProtection();

        $form->addText("login")
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Uživatelské jméno")
            ->setHtmlAttribute("class", "form-control")
            ->addRule($form::MAX_LENGTH, 'Uživatelské jméno nesmí být delší než %d znaků', 15)
            ->addRule($form::MIN_LENGTH, 'Uživatelské jméno musí být aspoň %d znaků dlouhé', 5)
            ->addRule($form::PATTERN, 'Uživatelské jméno musí obsahovat pouze znaky abecedy a číslice', '[a-zA-Z0-9]+');

        $form->addText("fName")
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Křestní jméno")
            ->setHtmlAttribute("class", "form-control")
            ->addRule($form::MAX_LENGTH, 'Křestní jméno nesmí být delší než %d znaků', 15)
            ->addRule($form::MIN_LENGTH, 'Křestní jméno musí být delší než %d znaky', 2)
            ->addRule($form::PATTERN, 'Křestní jméno musí obsahovat pouze znaky abecedy', '[a-zA-ZÀ-ž]+');


        $form->addText("lName")
            ->setRequired()
            ->setHtmlAttribute("placeholder", "Příjmení")
            ->setHtmlAttribute("class", "form-control")
            ->addRule($form::MAX_LENGTH, 'Příjmení nesmí být delší než %d znaků', 25)
            ->addRule($form::MIN_LENGTH, 'Příjmení musí být delší než %d znaky', 2)
            ->addRule($form::PATTERN, 'Příjmení musí obsahovat pouze znaky abecedy', '[a-zA-ZÀ-ž]+');

        $form->addRadioList("gender", "Pohlaví: ", [0 => "Muž", 1 => "Žena",])
            ->setHtmlAttribute("class", "form-check-input pointer")
            ->setDefaultValue(0)
            ->setRequired();

        $form->addPassword("password")
            ->setRequired()
            ->setHtmlAttribute("placeholder", 'Heslo')
            ->setHtmlAttribute("class", "form-control")
            ->addRule($form::MIN_LENGTH, 'Minimální délka hesla je %d znaků', 6)
            ->addRule($form::MAX_LENGTH, 'Maximální délka hesla je %d znaků', 15);

        $form->addPassword("confirmPassword")
            ->setRequired()
            ->setHtmlAttribute("placeholder", 'Potvrzení hesla')
            ->setHtmlAttribute("class", "form-control")
            ->addRule($form::EQUAL, 'Hesla se neshodují', $form['password'])
            ->addRule($form::MIN_LENGTH, 'Minimální délka hesla je %d znaků', 6)
            ->addRule($form::MAX_LENGTH, 'Maximální délka hesla je %d znaků', 15)
            ->setOmitted();

        $form->addReCaptcha('recaptcha', 'Captcha', TRUE);

        $form->addSubmit("submit", "Registrovat se")
            ->setHtmlAttribute("class", "btn btn-primary w-100")
            ->setHtmlAttribute("style", "border-radius: 10px;");

        $form->onSuccess[] = [$this, "registerSuccess"];

        return $form;
    }

    public function registerSuccess($form, $values)
    {

        $values = $form->getValues();

        $count = $this->database
            ->table('users')
            ->where('username = ? OR (fName = ? AND lName = ?)', $values->login, $values->fName, $values->lName)
            ->count();

        if ($count > 0) {
            $this->flashMessage("Uživatelské jméno již existuje", 'alert-danger');
            $this->redirect('this');
        } else {
            $this->database->table('users')->insert([
                'username' => $values->login,
                'fName' => ucfirst($values->fName),
                'lName' => ucfirst($values->lName),
                'gender' => $values->gender,
                'password' => password_hash($values->password, PASSWORD_DEFAULT),
            ]);
            $this->flashMessage("Registrace proběhla úspěšně!", 'alert-success');
            $this->redirect('User:login');
        }
    }
}