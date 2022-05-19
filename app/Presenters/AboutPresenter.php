<?php


declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class AboutPresenter extends BasePresenter
{

    final public function startup()
    {

        parent::startup();
        //print_r($this->getUser()->getIdentity()->getRoles()['role']);
    }
}