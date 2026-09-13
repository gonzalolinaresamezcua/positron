<?php

declare(strict_types=1);

namespace Positrom\Controllers\Admin;

use Positrom\Core\Env;
use Positrom\Core\Validator;
use Positrom\Core\View;
use Positrom\Models\Setting;
use Positrom\Services\EnvWriter;

final class SettingsController
{
    public function index(): void
    {
        View::render('admin/settings', [
            'title' => 'Ajustes',
            'env' => [
                'OPENAI_API_KEY' => Env::get('OPENAI_API_KEY', ''),
                'OPENAI_API_BASE' => Env::get('OPENAI_API_BASE', ''),
                'OPENAI_MODEL' => Env::get('OPENAI_MODEL', 'gpt-6-astra'),
                'OPENAI_CHAT_PATH' => Env::get('OPENAI_CHAT_PATH', '/v1/chat/completions'),
                'SMTP_HOST' => Env::get('SMTP_HOST', ''),
                'SMTP_PORT' => Env::get('SMTP_PORT', '587'),
                'SMTP_USER' => Env::get('SMTP_USER', ''),
                'SMTP_PASS' => Env::get('SMTP_PASS', ''),
                'SMTP_FROM' => Env::get('SMTP_FROM', ''),
                'SMTP_FROM_NAME' => Env::get('SMTP_FROM_NAME', 'POSITROM'),
                'SMTP_ENCRYPTION' => Env::get('SMTP_ENCRYPTION', 'tls'),
                'APP_URL' => Env::get('APP_URL', ''),
            ],
            'settings' => Setting::all(),
        ], 'layouts/admin');
    }

    public function saveKeys(): void
    {
        $keepIfBlank = ['OPENAI_API_KEY', 'SMTP_PASS'];
        $map = [
            'OPENAI_API_KEY' => (string) ($_POST['OPENAI_API_KEY'] ?? ''),
            'OPENAI_API_BASE' => (string) ($_POST['OPENAI_API_BASE'] ?? ''),
            'OPENAI_MODEL' => (string) ($_POST['OPENAI_MODEL'] ?? 'gpt-6-astra'),
            'OPENAI_CHAT_PATH' => (string) ($_POST['OPENAI_CHAT_PATH'] ?? '/v1/chat/completions'),
            'SMTP_HOST' => (string) ($_POST['SMTP_HOST'] ?? ''),
            'SMTP_PORT' => (string) ($_POST['SMTP_PORT'] ?? '587'),
            'SMTP_USER' => (string) ($_POST['SMTP_USER'] ?? ''),
            'SMTP_PASS' => (string) ($_POST['SMTP_PASS'] ?? ''),
            'SMTP_FROM' => (string) ($_POST['SMTP_FROM'] ?? ''),
            'SMTP_FROM_NAME' => (string) ($_POST['SMTP_FROM_NAME'] ?? 'POSITROM'),
            'SMTP_ENCRYPTION' => (string) ($_POST['SMTP_ENCRYPTION'] ?? 'tls'),
            'APP_URL' => (string) ($_POST['APP_URL'] ?? ''),
        ];
        foreach ($keepIfBlank as $secret) {
            if (trim($map[$secret]) === '') {
                unset($map[$secret]);
            }
        }
        EnvWriter::update($map);
        Setting::setMany([
            'openai.model' => $map['OPENAI_MODEL'] ?? (string) Setting::get('openai.model', 'gpt-6-astra'),
            'openai.api_base' => $map['OPENAI_API_BASE'] ?? '',
            'openai.chat_path' => $map['OPENAI_CHAT_PATH'] ?? '/v1/chat/completions',
        ]);
        set_flash('ok', 'Claves y endpoints guardados en .env de forma atómica.');
        redirect('/admin/ajustes');
    }

    public function saveUsage(): void
    {
        $v = new Validator($_POST);
        $v->required('token_input_cost', 'El coste de entrada')
            ->numeric('token_input_cost', 'El coste de entrada')
            ->required('token_output_cost', 'El coste de salida')
            ->numeric('token_output_cost', 'El coste de salida');
        if (!$v->ok()) {
            set_flash('error', $v->first());
            redirect('/admin/ajustes');
        }
        $in = number_format((float) $_POST['token_input_cost'], 4, '.', '');
        $out = number_format((float) $_POST['token_output_cost'], 4, '.', '');
        $allowance = trim((string) ($_POST['monthly_token_allowance'] ?? ''));

        Setting::setMany([
            'usage.token_input_cost_eur_per_1m' => $in,
            'usage.token_output_cost_eur_per_1m' => $out,
            'usage.monthly_token_allowance' => $allowance,
        ]);
        EnvWriter::update([
            'TOKEN_INPUT_COST_EUR_PER_1M' => $in,
            'TOKEN_OUTPUT_COST_EUR_PER_1M' => $out,
            'MONTHLY_TOKEN_ALLOWANCE' => $allowance,
        ]);
        set_flash('ok', 'Estadísticas de coste y tope opcional de tokens actualizados.');
        redirect('/admin/ajustes');
    }
}
