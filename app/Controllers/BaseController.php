<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class BaseController
{
    public function __construct(
        protected Twig $view,
    ) {}

    protected function render(Response $response, string $template, array $data = []): Response
    {
        if(isset($_SESSION['flash_message'])){
            $data['flash_message'] = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
        }
        return $this->view->render($response, $template, $data);
    }

    // TODO: add here any common controller logic and use in concrete controllers
    protected function addFlashMessage(string $type, string $message): void{
        if(!isset($_SESSION['flash_message'])){
            $_SESSION['flash_message'] = [];
        }

        $_SESSION['flash_message'][] = [
            'type' => $type,
            'message' => $message
        ];

    }
}
