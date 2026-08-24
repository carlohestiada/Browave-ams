<?php

require_once __DIR__ . '/../models/AuditLog.php';

class AuditLogController
{
    private $auditLog;

    public function __construct($db)
    {
        $this->auditLog = new AuditLog($db);
    }

    public function index()
    {
        $limit = $_GET['limit'] ?? 100;
        $offset = ($_GET['page'] ?? 1 - 1) * $limit;

        $logs = $this->auditLog->getAll($limit, $offset);
        $total = $this->auditLog->getTotalCount();

        echo json_encode([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'total' => $total,
                'page' => (int)($_GET['page'] ?? 1),
                'limit' => (int)$limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function byUser($userId)
    {
        $limit = $_GET['limit'] ?? 100;
        $offset = ($_GET['page'] ?? 1 - 1) * $limit;

        $logs = $this->auditLog->getByUser($userId, $limit, $offset);

        echo json_encode([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function byEntity($entityType, $entityId)
    {
        $logs = $this->auditLog->getByEntity($entityType, $entityId);

        echo json_encode([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function byDateRange()
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $limit = $_GET['limit'] ?? 100;
        $offset = ($_GET['page'] ?? 1 - 1) * $limit;

        $logs = $this->auditLog->getByDateRange($startDate, $endDate, $limit, $offset);

        echo json_encode([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function byAction($action)
    {
        $limit = $_GET['limit'] ?? 100;
        $offset = ($_GET['page'] ?? 1 - 1) * $limit;

        $logs = $this->auditLog->getByAction($action, $limit, $offset);

        echo json_encode([
            'success' => true,
            'data' => $logs
        ]);
    }
}
