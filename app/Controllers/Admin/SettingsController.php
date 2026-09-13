<?php

declare(strict_types=1);

namespace Positrom\Controllers\Admin;

use Positrom\Core\Config;
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
                'MOLLIE_API_KEY' => Env::get('MOLLIE_API_KEY', ''),
                'CURSOR_API_KEY' => Env::get('CURSOR_API_KEY', ''),
                'CURSOR_API_BASE' => Env::get('CURSOR_API_BASE', ''),
                'CURSOR_MODEL' => Env::get('CURSOR_MODEL', 'composer-2.5'),
                'CURSOR_CHAT_PATH' => Env::get('CURSOR_CHAT_PATH', '/v1/chat/completions'),
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
            'plan_price' => Setting::get('plan.price_eur', (string) Config::get('plan.price')),
        ], 'layouts/admin');
    }

    public function saveKeys(): void
    {
        $keepIfBlank = ['MOLLIE_API_KEY', 'CURSOR_API_KEY', 'SMTP_PASS'];
        $map = [
            'MOLLIE_API_KEY' => (string) ($_POST['MOLLIE_API_KEY'] ?? ''),
            'CURSOR_API_KEY' => (string) ($_POST['CURSOR_API_KEY'] ?? ''),
            'CURSOR_API_BASE' => (string) ($_POST['CURSOR_API_BASE'] ?? ''),
            'CURSOR_MODEL' => (string) ($_POST['CURSOR_MODEL'] ?? 'composer-2.5'),
            'CURSOR_CHAT_PATH' => (string) ($_POST['CURSOR_CHAT_PATH'] ?? '/v1/chat/completions'),
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
            'cursor.model' => $map['CURSOR_MODEL'] ?? (string) Setting::get('cursor.model', 'composer-2.5'),
            'cursor.api_base' => $map['CURSOR_API_BASE'] ?? '',
            'cursor.chat_path' => $map['CURSOR_CHAT_PATH'] ?? '/v1/chat/completions',
        ]);
        set_flash('ok', 'Claves y endpoints guardados en .env de forma atómica.');
        redirect('/admin/ajustes');
    }

    public function saveUsage(): void
    {
        $v = new Validator($_POST);
        $v->required('monthly_budget_eur', 'El presupuesto')
            ->numeric('monthly_budget_eur', 'El presupuesto')
            ->required('token_input_cost', 'El coste de entrada')
            ->numeric('token_input_cost', 'El coste de entrada')
            ->required('token_output_cost', 'El coste de salida')
            ->numeric('token_output_cost', 'El coste de salida');
        if (!$v->ok()) {
            set_flash('error', $v->first());
            redirect('/admin/ajustes');
        }
        $budget = number_format((float) $_POST['monthly_budget_eur'], 2, '.', '');
        $in = number_format((float) $_POST['token_input_cost'], 4, '.', '');
        $out = number_format((float) $_POST['token_output_cost'], 4, '.', '');
        $allowance = trim((string) ($_POST['monthly_token_allowance'] ?? ''));
        $auto = isset($_POST['auto_activate']) ? '1' : '0';
        $days = (string) max(1, (int) ($_POST['reminder_days'] ?? 3));

        Setting::setMany([
            'plan.price_eur' => '12.00',
            'usage.monthly_budget_eur' => $budget,
            'usage.token_input_cost_eur_per_1m' => $in,
            'usage.token_output_cost_eur_per_1m' => $out,
            'usage.monthly_token_allowance' => $allowance,
            'billing.auto_activate_on_payment' => $auto,
            'billing.reminder_days_before' => $days,
        ]);
        EnvWriter::update([
            'PLAN_PRICE_EUR' => '12.00',
            'MONTHLY_BUDGET_EUR' => $budget,
            'TOKEN_INPUT_COST_EUR_PER_1M' => $in,
            'TOKEN_OUTPUT_COST_EUR_PER_1M' => $out,
            'MONTHLY_TOKEN_ALLOWANCE' => $allowance,
            'AUTO_ACTIVATE_ON_PAYMENT' => $auto,
            'REMINDER_DAYS_BEFORE' => $days,
        ]);
        set_flash('ok', 'Tope de gasto y política de activación actualizados. El chat se detiene al agotar el presupuesto de 12 €.');
        redirect('/admin/ajustes');
    }
}
