<?php
/**
 * app/Controllers/PageController.php
 *
 * Controlador para renderizar páginas estáticas o con contenido simple.
 */

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\Plates\Engine as PlatesEngine;

class PageController
{
    private PlatesEngine $view;
    private $translator;

    public function __construct(PlatesEngine $view, callable $translator)
    {
        $this->view = $view;
        $this->translator = $translator;
    }

    public function showCookiePolicy(Request $request, Response $response): Response
    {
        $html = $this->view->render('pages/cookie_policy');
        $response->getBody()->write($html);
        return $response;
    }
}