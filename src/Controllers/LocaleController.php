<?php

declare(strict_types=1);

namespace App\Controllers;

use Spartan\Controller;

class LocaleController extends Controller
{
    /**
     * Switch application locale between en, ar, and fr.
     */
    public function switch(string $lang = 'en'): void
    {
        $lang = strtolower(trim($lang));
        $allowed = ['en', 'ar', 'fr'];

        if (in_array($lang, $allowed, true)) {
            $this->session->set('locale', $lang);
            \Spartan\Translation\Translator::getInstance()->setLocale($lang);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        if (str_contains($referer, '/locale/')) {
            $referer = '/dashboard';
        }

        $this->response->redirect($referer);
    }
}
