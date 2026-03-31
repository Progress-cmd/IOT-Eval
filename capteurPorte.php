<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$mqtt = new MqttClient('localhost', 1883, 'capteur-porte-producer');

$settings = (new ConnectionSettings)
    ->setUsername('capteur-user')
    ->setPassword('capteur123');

$mqtt->connect($settings, true);

echo "[*] Producer porte/fenêtre démarré (MQTT). Envoi toutes les 3 secondes...\n";
echo "[*] Appuyez sur Ctrl+C pour arrêter.\n\n";

/**
 * Selon le sujet :
 * - Porte ouverte en journée (avant 22h)          → INFO
 * - Porte ouverte la nuit (après 22h)              → WARNING
 * - Porte ouverte la nuit + mouvement simultané    → CRITICAL
 */
function getSeveritePorte(string $value): string
{
    if ($value === 'closed') {
        return 'info';
    }

    $heure = (int)date('H');
    $estNuit = $heure >= 22 || $heure < 6;


    if ($estNuit) {
        return 'warning';
    }

    return 'info';
}

while (true) {
    // Valeur : "open" ou "closed"
    $value = mt_rand(0, 1) === 1 ? 'open' : 'closed';


    $severity = getSeveritePorte($value);

    $data = [
        'sensor' => 'porte',
        'value' => $value,
        'room' => 'entree',
        'timestamp' => date('c'),
        'severity' => $severity,
    ];

    $message = json_encode($data);
    $topic = "alert.{$severity}.porte";

    $mqtt->publish($topic, $message, 0);

    echo "[x] Envoyé sur '{$topic}': {$message}\n";

    sleep(3);
}

$mqtt->disconnect();
