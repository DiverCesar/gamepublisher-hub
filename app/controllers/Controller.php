<?php
declare(strict_types=1);

namespace App\Controllers;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        $viewFile = BASE_PATH . '/app/views/' . $view . '.php';
        $headerFile = BASE_PATH . '/app/views/layouts/header.php';
        $footerFile = BASE_PATH . '/app/views/layouts/footer.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }

        $config = require BASE_PATH . '/config/config.php';
        $flash = $_SESSION['flash'] ?? [];
        $old = $_SESSION['_old_input'] ?? [];
        $errors = $_SESSION['_errors'] ?? [];

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require $headerFile;
        echo $content;
        require $footerFile;

        unset($_SESSION['flash'], $_SESSION['_old_input'], $_SESSION['_errors']);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $referer);
        exit;
    }

    protected function flash(string $key, mixed $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }

    protected function withInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    protected function withErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    protected function currentPage(string $key = 'page', int $default = 1): int
    {
        $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);

        if (!$value || $value < 1) {
            return $default;
        }

        return $value;
    }
}
