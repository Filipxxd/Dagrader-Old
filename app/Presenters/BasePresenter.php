<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

class BasePresenter extends Nette\Application\UI\Presenter
{

    public function __construct(private \Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }


    public function startup()
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('User:login');
            die();
        }
        $this->template->role = $this->getUser()->roles['role'];
    }
}