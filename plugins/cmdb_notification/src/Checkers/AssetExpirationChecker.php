<?php

namespace CmdbNotification\Checkers;

use PDO;
use Psr\Log\LoggerInterface;
use CmdbNotification\Services\Config;
use DateTime;
use DateInterval;

class AssetExpirationChecker
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
        return 'expiring_assets';
    }

    public function getExpiringItems(): array
    {
        $days = $this->config->get('notifications.days_advance', 30);
        $thresholdDate = (new DateTime())->add(new DateInterval("P{$days}D"))->format('Y-m-d');
        $today = date('Y-m-d');

        $sql = "
            SELECT a.id, a.nombre, a.numero_serie, ta.nombre AS tipo_activo_nombre,
                   a.fecha_fin_garantia, a.fecha_fin_mantenimiento,
                   a.fecha_fin_soporte_mainstream, a.fecha_fin_soporte_extended, a.fecha_fin_vida
            FROM activos a
            LEFT JOIN tipos_activos ta ON a.id_tipo_activo = ta.id
            WHERE a.id_estado != (SELECT id FROM estados_activo WHERE nombre = 'Retirado') AND (
                (a.fecha_fin_garantia BETWEEN :today AND :threshold_date) OR
                (a.fecha_fin_mantenimiento BETWEEN :today AND :threshold_date) OR
                (a.fecha_fin_soporte_mainstream BETWEEN :today AND :threshold_date) OR
                (a.fecha_fin_soporte_extended BETWEEN :today AND :threshold_date) OR
                (a.fecha_fin_vida BETWEEN :today AND :threshold_date)
            )
            ORDER BY a.nombre ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':today' => $today, ':threshold_date' => $thresholdDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error("Error al obtener activos a expirar: " . $e->getMessage());
            return [];
        }
    }

    public function render(array $items): string
    {
        $t = $this->translator;
        $days = $this->config->get('notifications.days_advance', 30);

        ob_start();
        // Incluimos una plantilla para renderizar el bloque de activos
        include __DIR__ . '/../Templates/expiring_assets_block.php';
        return ob_get_clean();
    }
}