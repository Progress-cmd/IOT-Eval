<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// Connexion au broker RabbitMQ via MQTT (port 1883)
$mqtt = new MqttClient('localhost', 1883, 'capteur-fumee-producer');

$settings = (new ConnectionSettings)
    ->setUsername('capteur-user')
    ->setPassword('capteur123');

$mqtt->connect($settings, true);

echo "[*] Producer fumée démarré (MQTT). Envoi toutes les 3 secondes...\n";
echo "[*] Appuyez sur Ctrl+C pour arrêter.\n\n";

// Fonction pour déterminer la sévérité selon le sujet
function getSeveriteFumee(int $value):string {
    if ($value > 70) {
        return 'critical';
    }
    return 'info'; // Pas de warning pour la fumée selon le sujet
}

while (true) {
    // On génère la valeur une seule fois
    $value = mt_rand(0, 100);
    $severity = getSeveriteFumee($value);

    // Construction du message JSON
    $data = [
        'sensor'    => 'fumee',
        'value'     => $value,
        'room'      => 'cuisine',
        'timestamp' => date('c'),
        'severity'  => $severity,
    ];

    $message = json_encode($data);

    // Routing key dynamique selon la sévérité : alert.<severity>.<sensor>
    // Permet au Topic Exchange de filtrer par niveau
    $topic = "alert.{$severity}.fumee";

    $mqtt->publish($topic, $message, 0);

    echo "[x] Envoyé sur '{$topic}': {$message}\n";

    sleep(3);
}

$mqtt->disconnect();