<?php

declare(strict_types=1);

namespace Positrom\Controllers\Admin;

use Positrom\Core\View;
use Positrom\Models\Payment;

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
