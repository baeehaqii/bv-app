<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    // ponytail: base layout instead of the centered "simple" card so the blade can go full-width split-screen
    protected static string $layout = 'filament-panels::components.layout.base';

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()->placeholder('Enter your email');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->placeholder('Enter your password');
    }
}
