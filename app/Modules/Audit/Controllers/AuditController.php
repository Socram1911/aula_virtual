<?php

declare(strict_types=1);

namespace App\Modules\Audit\Controllers;

use App\Core\Controller;
use App\Core\Database\Connection;

final class AuditController extends Controller
{
    public function index(): void
    {
        $this->requireRole('ADMIN');
        $logs = Connection::fetchAll(
            'SELECT al.*, u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.id DESC LIMIT 200'
        );
        $this->render('Audit/index', ['title' => 'Auditoría', 'logs' => $logs]);
    }
}
