<?php

namespace App\Core;

class View
{
    /**
     * Renders app/Views/{$template}.php inside app/Views/layout/app.php,
     * unless $layout is set to null for a bare render (e.g. login page).
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layout/app'): void
    {
        $data['currentUser'] = Auth::user();
        $data['flash'] = Flash::consume();

        $renderInner = function () use ($template, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            require APP_ROOT . '/app/Views/' . $template . '.php';
            return ob_get_clean();
        };

        if ($layout === null) {
            echo $renderInner();
            return;
        }

        $content = $renderInner();
        extract($data, EXTR_SKIP);
        require APP_ROOT . '/app/Views/' . $layout . '.php';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
