<?php

namespace VelocityMarketplace\Modules\Captcha;

class CaptchaBridge
{
    private static $handler = null;

    public static function get_handler()
    {
        if (self::$handler !== null) {
            return self::$handler;
        }

        global $captcha_handler;
        if (is_object($captcha_handler) && method_exists($captcha_handler, 'verify')) {
            self::$handler = $captcha_handler;
            return self::$handler;
        }

        if (!class_exists('Velocity_Addons_Captcha')) {
            return null;
        }

        try {
            $reflection = new \ReflectionClass('Velocity_Addons_Captcha');
            $handler = $reflection->newInstanceWithoutConstructor();

            $settings = get_option('captcha_velocity', []);
            if (!is_array($settings)) {
                $settings = [];
            }

            $sitekey = isset($settings['sitekey']) ? (string) $settings['sitekey'] : '';
            $secretkey = isset($settings['secretkey']) ? (string) $settings['secretkey'] : '';
            $provider = isset($settings['provider']) ? (string) $settings['provider'] : 'google';
            $difficulty = isset($settings['difficulty']) ? (string) $settings['difficulty'] : 'medium';
            $size = wp_is_mobile() ? 'compact' : 'normal';
            $active = !empty($settings['aktif']) && (
                ($provider === 'google' && $sitekey !== '' && $secretkey !== '') ||
                $provider === 'image'
            );

            foreach ([
                'sitekey' => $sitekey,
                'secretkey' => $secretkey,
                'size' => $size,
                'provider' => $provider,
                'difficulty' => $difficulty,
                'active' => $active,
            ] as $property => $value) {
                if (!$reflection->hasProperty($property)) {
                    continue;
                }

                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($handler, $value);
            }

            self::$handler = $handler;
            return self::$handler;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function is_available()
    {
        return self::get_handler() !== null;
    }

    public static function is_active()
    {
        $handler = self::get_handler();
        if (!$handler) {
            return false;
        }

        if (method_exists($handler, 'isActive')) {
            return (bool) $handler->isActive();
        }

        return true;
    }

    public static function render($form_selector = '')
    {
        if (!self::is_available() || !self::is_active()) {
            return '';
        }

        $selector_attr = trim((string) $form_selector);
        $shortcode = '[velocity_captcha';
        if ($selector_attr !== '') {
            $shortcode .= ' form="' . esc_attr($selector_attr) . '"';
        }
        $shortcode .= ']';

        return do_shortcode($shortcode);
    }

    public static function verify_payload($payload = [])
    {
        $handler = self::get_handler();
        if (!$handler) {
            return [
                'success' => true,
                'message' => 'Captcha handler tidak ditemukan.',
            ];
        }

        if (method_exists($handler, 'isActive') && !$handler->isActive()) {
            return [
                'success' => true,
                'message' => 'Captcha tidak aktif.',
            ];
        }

        $gresponse = '';
        $token = '';
        $input = '';
        if (is_array($payload)) {
            $gresponse = isset($payload['g-recaptcha-response']) ? (string) $payload['g-recaptcha-response'] : '';
            if ($gresponse === '' && isset($payload['g_recaptcha_response'])) {
                $gresponse = (string) $payload['g_recaptcha_response'];
            }
            $token = isset($payload['vd_captcha_token']) ? (string) $payload['vd_captcha_token'] : '';
            $input = isset($payload['vd_captcha_input']) ? (string) $payload['vd_captcha_input'] : '';
        }

        $backup_post = $_POST;
        $_POST = is_array($_POST) ? $_POST : [];
        if ($gresponse !== '') {
            $_POST['g-recaptcha-response'] = $gresponse;
        }
        if ($token !== '') {
            $_POST['vd_captcha_token'] = $token;
        }
        if ($input !== '') {
            $_POST['vd_captcha_input'] = $input;
        }

        $verify = $handler->verify($gresponse !== '' ? $gresponse : null);

        $_POST = $backup_post;
        if (!is_array($verify)) {
            return [
                'success' => false,
                'message' => 'Captcha tidak valid.',
            ];
        }

        return [
            'success' => !empty($verify['success']),
            'message' => isset($verify['message']) ? (string) $verify['message'] : '',
        ];
    }

    public static function verify_request()
    {
        $payload = [];

        if (is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    $payload[$key] = wp_unslash($value);
                } else {
                    $payload[$key] = (string) wp_unslash($value);
                }
            }
        }

        return self::verify_payload($payload);
    }
}


