<?php

namespace App\Core;

/**
 * Controller base. Todos os controllers da aplicação devem herdar desta classe.
 */
abstract class Controller
{
    /**
     * Renderiza uma view a partir de views/{$view}.php
     *
     * @param string $view Caminho relativo da view, ex: 'home/index'
     * @param array  $data Variáveis disponibilizadas dentro da view
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = APP_ROOT . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View não encontrada: {$view}";
            return;
        }

        require $viewFile;
    }

    /**
     * Retorna uma resposta JSON e encerra a execução.
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Redireciona para outra rota interna.
     */
    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    /**
     * Lê e decodifica o corpo JSON de uma requisição.
     */
    protected function jsonInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Exige um token CSRF válido (header X-CSRF-Token) em endpoints que
     * alteram estado. Responde 403 e encerra a execução se inválido.
     */
    protected function requireCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!\App\Core\Csrf::validate($token)) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido ou ausente.'], 403);
        }
    }
}
