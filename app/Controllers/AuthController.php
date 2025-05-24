<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

//Login user:
//using the proper password verify function in PHP.
//prevent session fixation attacks.
//make the login user form CSRF-proof. - i couldn't make it

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user registration
        $data = $request->getParsedBody();
        $username = $data['username'] ?? ''; // "??" Null coalescing	$x = expr1 ?? expr2
        $password = $data['password'] ?? '';
        $passwordConfirmation = $data['password_confirm'] ?? '';

        $result=$this->authService->register($username, $password, $passwordConfirmation);

        if(!$result['successful']){
            return $this->view->render($response, 'auth/register.twig', [
                'username' => $username,
                'errors' => $result['errors'],
            ]);
        }
        $this->logger->info(sprintf('User %s Registered.', $username));
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if(!$this->authService->attempt($username, $password)){
            return $this->view->render($response, 'auth/login.twig', [
                'username' => $username,
                'errors' => 'Log in failed. Try again. Check username and password',
            ]);
        }
        $this->logger->info(sprintf('User %s Logged in.', $username));
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session
        if(session_status() === PHP_SESSION_ACTIVE){
            $_SESSION = [];
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']);
            session_destroy();
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
