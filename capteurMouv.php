<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$mqtt = new MqttClient('localhost', 1883, 'capteur-mouvement-producer');

$settings = (new ConnectionSettings)
    ->setUsername('capteur-user')
    ->setPassword('capteur123');

$mqtt->connect($settings, true);

echo "[*] Producer mouvement démarré (MQTT). Envoi toutes les 3 secondes...\n";
echo "[*] Appuyez sur Ctrl+C pour arrêter.\n\n";

// Selon le sujet : mouvement détecté → toujours INFO
function getSeveriteMouvement(): string
{
    return 'info';
}

while (true) {
    // Valeur booléenne : true ou false aléatoirement
    $value = (bool)mt_rand(0, 1);
    $severity = getSeveriteMouvement();

    $data = [
        'sensor' => 'mouvement',
        'value' => $value,
        'room' => 'salon',
        'timestamp' => date('c'),
        'severity' => $severity,
    ];

    $message = json_encode($data);
    $topic = "alert.{$severity}.mouvement";

    $mqtt->publish($topic, $message, 0);

    echo "[x] Envoyé sur '{$topic}': {$message}\n";

    sleep(3);
}

$mqtt->disconnect();
