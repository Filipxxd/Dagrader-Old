<?php

declare(strict_types=1);

use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;

class Authenticator implements \Nette\Security\Authenticator
{
    public function __construct(private \Nette\Database\Explorer $database, private \Nette\Security\Passwords $passwords)
    {
    }

    public function authenticate(string $userLogin, string $password): IIdentity
    {
        $user = $this->database->table('users')->where('username = ?', $userLogin)->fetch();

        if ($user === null) {
            throw new \Nette\Security\AuthenticationException('Uživatel nenalezen');
        }

        if ($this->passwords->verify($password, $user->password) === false) {
            throw new \Nette\Security\AuthenticationException('Chybné heslo');
        }


        return new \Nette\Security\SimpleIdentity(
            $user->userId,
            [
                'role' => $user->role,
            ],
            [
                'fName' => $user->fName,
                'lName' => $user->lName,
                'gender' => $user->gender,
            ]
        );
    }
}