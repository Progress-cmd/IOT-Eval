<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$mqtt = new MqttClient('localhost', 1883, 'capteur-temperature-producer');

$settings = (new ConnectionSettings)
    ->setUsername('capteur-user')
    ->setPassword('capteur123');

$mqtt->connect($settings, true);

echo "[*] Producer température démarré (MQTT). Envoi toutes les 3 secondes...\n";
echo "[*] Appuyez sur Ctrl+C pour arrêter.\n\n";

/**
 * Selon le sujet :
 * - Température <= 35°C   → INFO
 * - Température > 35°C    → WARNING
 * - Température > 50°C    → CRITICAL
 */
function getSeveriteTemperature(float $value): string
{
    if ($value > 50) {
        return 'critical';
    }

    if ($value > 35) {
        return 'warning';
    }

    return 'info';
}

while (true) {
    // Valeur décimale entre 15 et 65°C pour couvrir tous les cas
    $value = round(mt_rand(1500, 6500) / 100, 2);
    $severity = getSeveriteTemperature($value);

    $data = [
        'sensor' => 'temperature',
        'value' => $value,
        'room' => 'chambre',
        'timestamp' => date('c'),
        'severity' => $severity,
    ];

    $message = json_encode($data);
    $topic = "alert.{$severity}.temperature";

    $mqtt->publish($topic, $message, 0);

    echo "[x] Envoyé sur '{$topic}': {$message}\n";

    sleep(3);
}

$mqtt->disconnect();
