<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Connexion au serveur RabbitMQ
$connection = new AMQPStreamConnection('localhost', 5672, 'capteur-user', 'capteur123');
$channel = $connection->channel();

// Declaration du Topic Exchange "domotique"
// Les parametres : nom, type, passive, durable, auto_delete
//$channel->exchange_declare('domotique', 'topic', false, true, false);

echo "[*] Producer demarre. Envoi de messages toutes les 3 secondes...\n";
echo "[*] Appuyez sur Ctrl+C pour arreter.\n\n";

$capteur_fumee = [

[
    'routing_key' => 'maison.cuisine.fumee',
    'sensor' => 'fumee',
    'room' => 'cuisine',
    // Genere un niveau de fumee entre 0 et 100
    'value_fn' => function () {
        return mt_rand(0, 100);
        ''
    },
],
];
while (true) {

    // Construction du message en JSON
    $data = [
        'sensor' => $capteur_fumee['sensor'],
        'value' => ($capteur_fumee['value_fn'])(),
        'room' => $capteur_fumee['room'],
        'timestamp' => date('c'),
        'severity' => ($capteur_fumee['value_fn'])() > 70 ? 'high' : 'normal', // Niveau de gravite en fonction de la valeur
    ];

    $message = json_encode($data);

    // Publication du message sur l'exchange avec la routing key
    $msg = new AMQPMessage($message, [
        'content_type' => 'application/json',
    ]);
    $channel->basic_publish($msg, 'domotique', $capteur_fumee['routing_key']);

    echo "[x] Envoye {$capteur_fumee['routing_key']}: $message\n";

    // Pause de 3 secondes entre chaque envoi
    sleep(3);
}

// Fermeture propre (jamais atteint dans la boucle infinie)
$channel->close();
$connection->close();
