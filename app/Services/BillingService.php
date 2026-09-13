<?php

declare(strict_types=1);

namespace Positrom\Services;

use Positrom\Core\Config;
use Positrom\Models\Payment;
use Positrom\Models\Setting;
use Positrom\Models\Subscription;
use Positrom\Models\User;

final class BillingService
{
    public function __construct(
        private MollieClient $mollie = new MollieClient(),
        private Mailer $mailer = new Mailer()
    ) {
    }

    public function planPrice(): float
    {
        $v = Setting::get('plan.price_eur');
        return $v !== null && $v !== '' ? (float) $v : (float) Config::get('plan.price', 12.0);
    }

    public function autoActivate(): bool
    {
        $v = Setting::get('billing.auto_activate_on_payment');
        if ($v !== null && $v !== '') {
            return $v === '1';
        }
        return (bool) Config::get('billing.auto_activate', true);
    }

    public function startCheckout(array $user): string
    {
        $price = $this->planPrice();
        $subId = Subscription::createForUser((int) $user['id'], $price);
        $sub = Subscription::find($subId);
        if ($sub === null) {
            throw new \RuntimeException('No se pudo crear la suscripción local.');
        }

        $customerId = $sub['mollie_customer_id'];
        if ($customerId === null || $customerId === '') {
            $customer = $this->mollie->createCustomer([
                'name' => $user['name'],
                'email' => $user['email'],
                'metadata' => ['user_id' => (string) $user['id']],
            ]);
            $customerId = $customer['id'];
            Subscription::update($subId, ['mollie_customer_id' => $customerId]);
        }

        $amount = number_format($price, 2, '.', '');
        $payment = $this->mollie->createPayment([
            'amount' => ['currency' => 'EUR', 'value' => $amount],
            'customerId' => $customerId,
            'sequenceType' => 'first',
            'description' => 'POSITROM — primer mes (12 €)',
            'redirectUrl' => url('/checkout/retorno'),
            'webhookUrl' => url('/webhooks/mollie'),
            'metadata' => [
                'user_id' => (string) $user['id'],
                'subscription_id' => (string) $subId,
                'kind' => 'first',
            ],
        ]);

        Payment::upsertFromMollie([
            'user_id' => (int) $user['id'],
            'subscription_id' => $subId,
            'mollie_payment_id' => $payment['id'],
            'amount_eur' => $price,
            'currency' => 'EUR',
            'status' => $payment['status'] ?? 'open',
            'method' => $payment['method'] ?? null,
            'sequence_type' => 'first',
            'is_first' => 1,
            'raw_json' => json_encode($payment, JSON_UNESCAPED_UNICODE),
        ]);

        $checkout = $this->mollie->checkoutUrl($payment);
        if ($checkout === null) {
            throw new \RuntimeException('Mollie no devolvió URL de checkout.');
        }
        return $checkout;
    }

    public function handleWebhook(string $paymentId): void
    {
        $remote = $this->mollie->getPayment($paymentId);
        $meta = $remote['metadata'] ?? [];
        $userId = isset($meta['user_id']) ? (int) $meta['user_id'] : 0;
        $local = Payment::findByMollieId($paymentId);
        if ($userId < 1 && $local !== null) {
            $userId = (int) $local['user_id'];
        }
        if ($userId < 1 && isset($remote['customerId'])) {
            $sub = $this->subscriptionByCustomer((string) $remote['customerId']);
            $userId = $sub !== null ? (int) $sub['user_id'] : 0;
        }
        if ($userId < 1) {
            return;
        }
        $sub = Subscription::forUser($userId);
        if ($sub === null) {
            $subId = Subscription::createForUser($userId, $this->planPrice());
            $sub = Subscription::find($subId);
        }

        $amount = isset($remote['amount']['value']) ? (float) $remote['amount']['value'] : $this->planPrice();
        $paidAt = isset($remote['paidAt']) ? date('Y-m-d H:i:s', strtotime((string) $remote['paidAt'])) : null;
        $isFirst = (($remote['sequenceType'] ?? '') === 'first') || !empty($meta['kind']) && $meta['kind'] === 'first';

        Payment::upsertFromMollie([
            'user_id' => $userId,
            'subscription_id' => $sub['id'] ?? null,
            'mollie_payment_id' => $paymentId,
            'amount_eur' => $amount,
            'currency' => $remote['amount']['currency'] ?? 'EUR',
            'status' => $remote['status'] ?? 'unknown',
            'method' => $remote['method'] ?? null,
            'sequence_type' => $remote['sequenceType'] ?? null,
            'is_first' => $isFirst ? 1 : 0,
            'paid_at' => $paidAt,
            'raw_json' => json_encode($remote, JSON_UNESCAPED_UNICODE),
        ]);

        $status = $remote['status'] ?? '';
        if ($status === 'paid') {
            $this->onPaid($userId, $sub, $remote, $isFirst);
        } elseif (in_array($status, ['failed', 'expired', 'canceled', 'chargeback'], true)) {
            if ($sub !== null && ($sub['status'] ?? '') === 'active') {
                Subscription::update((int) $sub['id'], ['status' => 'past_due']);
            }
        }
    }

    public function activate(int $userId, ?string $reason = null): void
    {
        $sub = Subscription::forUser($userId);
        if ($sub === null) {
            throw new \RuntimeException('El cliente no tiene suscripción.');
        }
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+1 month'));
        Subscription::update((int) $sub['id'], [
            'status' => 'active',
            'activated_at' => $sub['activated_at'] ?: now(),
            'current_period_start' => $sub['current_period_start'] ?: $start,
            'current_period_end' => $sub['current_period_end'] ?: $end,
            'next_payment_date' => $sub['next_payment_date'] ?: $end,
            'cancel_reason' => $reason,
        ]);
        $user = User::find($userId);
        if ($user !== null) {
            $this->mailer->subscriptionActivated($user);
        }
    }

    public function cancel(int $userId, string $reason = 'Cancelada'): void
    {
        $sub = Subscription::forUser($userId);
        if ($sub === null) {
            return;
        }
        if (!empty($sub['mollie_customer_id']) && !empty($sub['mollie_subscription_id']) && $this->mollie->configured()) {
            try {
                $this->mollie->cancelSubscription((string) $sub['mollie_customer_id'], (string) $sub['mollie_subscription_id']);
            } catch (\Throwable) {
                // Se cancela en local aunque Mollie falle; el admin puede reintentar.
            }
        }
        Subscription::update((int) $sub['id'], [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'mollie_subscription_id' => $sub['mollie_subscription_id'],
        ]);
    }

    public function sendDueReminders(): int
    {
        $days = (int) (Setting::get('billing.reminder_days_before') ?: Config::get('billing.reminder_days', 3));
        $due = Subscription::dueForReminder($days);
        $sent = 0;
        foreach ($due as $row) {
            $period = period_ym((string) $row['next_payment_date']);
            if (\Positrom\Models\Reminder::alreadySent((int) $row['user_id'], $period)) {
                continue;
            }
            $ok = $this->mailer->paymentReminder(
                ['id' => $row['user_id'], 'email' => $row['email'], 'name' => $row['name']],
                $row
            );
            \Positrom\Models\Reminder::mark((int) $row['user_id'], $period);
            if ($ok) {
                $sent++;
            } else {
                $sent++; // registrado aunque SMTP esté en placeholder
            }
        }
        return $sent;
    }

    public function syncRemoteSubscriptions(): int
    {
        $updated = 0;
        foreach (Subscription::allWithUsers() as $sub) {
            if (empty($sub['mollie_customer_id']) || empty($sub['mollie_subscription_id']) || !$this->mollie->configured()) {
                continue;
            }
            try {
                $remote = $this->mollie->getSubscription((string) $sub['mollie_customer_id'], (string) $sub['mollie_subscription_id']);
            } catch (\Throwable) {
                continue;
            }
            $fields = [];
            if (!empty($remote['nextPaymentDate'])) {
                $fields['next_payment_date'] = $remote['nextPaymentDate'];
            }
            if (($remote['status'] ?? '') === 'canceled' && $sub['status'] !== 'cancelled') {
                $fields['status'] = 'cancelled';
                $fields['cancelled_at'] = now();
            }
            if ($fields !== []) {
                Subscription::update((int) $sub['id'], $fields);
                $updated++;
            }
        }
        return $updated;
    }

    private function onPaid(int $userId, ?array $sub, array $remote, bool $isFirst): void
    {
        if ($sub === null) {
            return;
        }
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+1 month'));
        $fields = [
            'current_period_start' => $start,
            'current_period_end' => $end,
            'next_payment_date' => $end,
        ];
        if (!empty($remote['mandateId'])) {
            $fields['mollie_mandate_id'] = $remote['mandateId'];
        }
        if (!empty($remote['customerId'])) {
            $fields['mollie_customer_id'] = $remote['customerId'];
        }

        if ($isFirst && empty($sub['mollie_subscription_id']) && !empty($sub['mollie_customer_id'] ?? $remote['customerId'])) {
            $customerId = (string) ($sub['mollie_customer_id'] ?? $remote['customerId']);
            try {
                $created = $this->mollie->createSubscription($customerId, [
                    'amount' => ['currency' => 'EUR', 'value' => number_format($this->planPrice(), 2, '.', '')],
                    'interval' => '1 month',
                    'startDate' => $end,
                    'description' => 'POSITROM suscripción mensual 12 €',
                    'webhookUrl' => url('/webhooks/mollie'),
                    'metadata' => ['user_id' => (string) $userId],
                ]);
                $fields['mollie_subscription_id'] = $created['id'] ?? null;
                if (!empty($created['nextPaymentDate'])) {
                    $fields['next_payment_date'] = $created['nextPaymentDate'];
                }
            } catch (\Throwable $e) {
                $fields['cancel_reason'] = 'Alta Mollie pendiente: ' . mb_substr($e->getMessage(), 0, 180);
            }
        }

        if ($this->autoActivate()) {
            $fields['status'] = 'active';
            $fields['activated_at'] = $sub['activated_at'] ?: now();
        } elseif (($sub['status'] ?? '') !== 'active') {
            $fields['status'] = 'pending_activation';
        }

        Subscription::update((int) $sub['id'], $fields);
        $user = User::find($userId);
        if ($user !== null) {
            $this->mailer->paymentReceived($user, money_eur((float) ($remote['amount']['value'] ?? $this->planPrice())));
            if (($fields['status'] ?? '') === 'active' && ($sub['status'] ?? '') !== 'active') {
                $this->mailer->subscriptionActivated($user);
            }
        }
    }

    private function subscriptionByCustomer(string $customerId): ?array
    {
        return \Positrom\Core\Database::fetch(
            'SELECT * FROM subscriptions WHERE mollie_customer_id = ? LIMIT 1',
            [$customerId]
        );
    }
}
