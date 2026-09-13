<?php

declare(strict_types=1);

namespace Positrom\Controllers\Admin;

use Positrom\Core\View;
use Positrom\Models\User;
use Positrom\Services\UsageLimiter;

final class ClientsController
{
    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $users = User::paginate($page, 30, $q !== '' ? $q : null);
        $enriched = [];
        foreach ($users as $u) {
            $u['usage'] = (new UsageLimiter())->snapshot((int) $u['id']);
            $enriched[] = $u;
        }
        View::render('admin/clients', [
            'title' => 'Usuarios',
            'users' => $enriched,
            'q' => $q,
            'page' => $page,
            'total' => User::count($q !== '' ? $q : null),
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'No encontrado'], 'layouts/admin');
            return;
        }
        View::render('admin/client', [
            'title' => $user['name'],
            'client' => $user,
            'usage' => (new UsageLimiter())->snapshot((int) $user['id']),
        ], 'layouts/admin');
    }

    public function toggle(string $id): void
    {
        $user = User::find((int) $id);
        if ($user === null || $user['role'] === 'admin') {
            set_flash('error', 'No se puede desactivar esta cuenta.');
            redirect('/admin/clientes');
        }
        User::setActive((int) $id, !((int) $user['is_active']));
        set_flash('ok', 'Estado de la cuenta actualizado.');
        redirect('/admin/clientes/' . (int) $id);
    }
}
