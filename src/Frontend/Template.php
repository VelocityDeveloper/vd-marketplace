<?php

namespace VelocityMarketplace\Frontend;

class Template
{
    public static function render($template, $data = [])
    {
        $file = self::locate($template);
        if (!file_exists($file)) {
            return '';
        }

        if (is_array($data)) {
            extract($data, EXTR_SKIP);
        }

        ob_start();
        include $file;
        return ob_get_clean();
    }

    public static function locate($template)
    {
        return VMP_PATH . 'templates/' . ltrim($template, '/') . '.php';
    }
}
