<?php

declare(strict_types=1);

namespace Positron\Controllers\Admin;

use Positron\Core\View;
use Positron\Models\Payment;

final class PaymentsController
{
    public function index(): void
    {
        View::render('admin/payments', [
            'title' => 'Pagos',
            'payments' => Payment::recent(200),
            'revenue' => Payment::sumPaid(),
            'count' => Payment::countPaid(),
        ], 'layouts/admin');
    }
}
