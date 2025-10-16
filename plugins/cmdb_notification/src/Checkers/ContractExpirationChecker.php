<?php

namespace CmdbNotification\Checkers;

use PDO;
use Psr\Log\LoggerInterface;
use CmdbNotification\Services\Config;
use DateTime;
use DateInterval;

class ContractExpirationChecker
{
    private PDO $db;
    private LoggerInterface $logger;
    private $translator;
    private Config $config;

    public function __construct(PDO $db, LoggerInterface $logger, callable $translator, Config $config)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->config = $config;
    }

    public function getNotificationKey(): string
    {
        return 'expiring_contracts';
    }

    public function getExpiringItems(): array
    {
        $days = $this->config->get('notifications.days_advance', 30);
        $thresholdDate = (new DateTime())->add(new DateInterval("P{$days}D"))->format('Y-m-d');
        $today = date('Y-m-d');

        $sql = "
            SELECT c.id, c.numero_contrato, ct.nombre AS tipo_contrato_nombre, p.nombre AS proveedor_nombre, c.fecha_fin
            FROM contratos c
            LEFT JOIN tipos_contrato ct ON c.id_tipo_contrato = ct.id
            LEFT JOIN proveedores p ON c.id_proveedor = p.id
            WHERE c.fecha_fin BETWEEN :today AND :threshold_date
            ORDER BY c.fecha_fin ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':today' => $today, ':threshold_date' => $thresholdDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error("Error al obtener contratos a expirar: " . $e->getMessage());
            return [];
        }
    }

    public function render(array $items): string
    {
        $t = $this->translator;
        $days = $this->config->get('notifications.days_advance', 30);

        ob_start();
        include __DIR__ . '/../Templates/expiring_contracts_block.php';
        return ob_get_clean();
    }
}