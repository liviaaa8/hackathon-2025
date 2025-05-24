<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    //Validation rules: username (≥ 4 chars), password (≥ 8 chars, 1 number).

    private const MIN_USERNAME_LENGTH = 4;
    private const MIN_PASSWORD_LENGTH = 8;
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}


    //Login user:
    //using the proper password verify function in PHP.
    //prevent session fixation attacks.
    //make the login user form CSRF-proof.

    public function register(string $username, string $password, $passwordConfirmation): array //for error display
    {
        //Register user:
        //using the proper password hashing function in PHP.
        //implement a “password again” input for ensuring no password typos.
        //make the register user form CSRF-proof.

        $validationErrors = $this->validateRegistration($username, $password, $passwordConfirmation);
        if (!empty($validationErrors)) {
            return ['successful' => false, 'errors' => $validationErrors];
        }
        // TODO: check that a user with same username does not exist, create new user and persist

        if ($this->usernameAlreadyExists($username))
        {
            return ['successful' => false, 'errors' => ['username' => 'This username already taken, choose another username.']];
        }

        $newUser = $this->createHashedPasswordUser($username, $password);
        $this->users->save($newUser);

        return ['successful' => true, 'user' => $newUser];

        
        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that
        // TODO: here is a sample code to start with
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: implement this for authenticating the user
        // TODO: make sure the user exists and the password matches
        $user = $this->users->findByUsername($username);

        if ($user == null || !password_verify($password, $user->passwordHash)) {
            return false;
        }

        $this->startSecureUserSession($user);
        return true;
        // TODO: don't forget to store in session user data needed afterwards

    }
    private function usernameAlreadyExists(string $username): bool
    {
        return $this->users->findByUsername($username) !== null;
    }

    private function validateRegistration(string $username, string $password, string $passwordConfirmation):array
    {
        $errors = [];
        if (strlen($username) < self::MIN_USERNAME_LENGTH) {
            $errors['username'] = sprintf('Username must contain at least %d characters long', self::MIN_USERNAME_LENGTH);
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = sprintf('Password must contain at least %d characters long', self::MIN_PASSWORD_LENGTH);
        }

        if (!preg_match('/[0-9]/', $password)) { //Searches subject for a match to the regular expression given in pattern.
            $errors['password'] = 'Password must contain at least one number';
        }

         if ($password !== $passwordConfirmation) {
          $errors['password'] = 'Passwords do not match';
        }

        return $errors;
    }

    private function startSecureUserSession(User $user): void
    {
        $this->startSession();
        $this->regenerateSessionId();

        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
    }
        private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function regenerateSessionId():void
    {
        session_regenerate_id(true);
    }

    private function createHashedPasswordUser(string $username, string $password):User
    {
        return new User(
            null,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            new \DateTimeImmutable()
        );
    }
}
