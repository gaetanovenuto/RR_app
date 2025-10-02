<?php
require_once '../config.php';


$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$conn->query("DROP DATABASE IF EXISTS $dbname"); // Se esiste, droppa DB
$conn->query("CREATE DATABASE $dbname"); // Ricrea DB Vuoto
$conn->query("USE $dbname"); // Usa db appena creato

// Crea tabella utenti
$conn->query("CREATE TABLE users(
id INT NOT NULL AUTO_INCREMENT,
firstname varchar(128) NOT NULL,
lastname varchar(128) NOT NULL,
email varchar(255) NOT NULL,
username varchar(128) NOT NULL,
password varchar(512) NOT NULL,
role INT NOT NULL,
enabled INT NOT NULL,
token varchar(1024),
token_expires_at DATETIME,
confirmed_at DATETIME,
created_at DATETIME,
updated_at DATETIME,
PRIMARY KEY (id),
UNIQUE (id, email));");


// Crea tabella stanze
$conn->query("CREATE TABLE rooms(
id INT NOT NULL AUTO_INCREMENT,
name varchar(128) NOT NULL,
seats INT NOT NULL,
opening_range JSON NOT NULL,
time_frame INT NOT NULL,
availability INT NOT NULL,
reservation_gap INT NOT NULL,
created_at DATETIME,
updated_at DATETIME,
PRIMARY KEY (id),
UNIQUE (name));");

$conn->query("CREATE TABLE mail_templates(
id INT NOT NULL AUTO_INCREMENT,
type varchar(255) NOT NULL,
subject varchar(512) NOT NULL,
body LONGTEXT NOT NULL,
enabled INT NOT NULL,
created_at DATETIME,
updated_at DATETIME,
PRIMARY KEY (id),
UNIQUE (type));");

$conn->query("CREATE TABLE mails(
id INT NOT NULL AUTO_INCREMENT,
recipient varchar(512) NOT NULL,
subject varchar(512) NOT NULL,
body LONGTEXT NOT NULL,
state TINYINT NOT NULL,
error TEXT,
created_at DATETIME,
updated_at DATETIME,
PRIMARY KEY (id));");

$conn->query("CREATE TABLE events(
id INT NOT NULL AUTO_INCREMENT,
name varchar(512) NOT NULL,
user_id INT NOT NULL,
room_id INT NOT NULL,
starting_time DATETIME NOT NULL,
ending_time DATETIME NOT NULL,
access_key varchar(1024) NOT NULL,
alert_time INT NOT NULL,
guestsEmail TEXT,
notes TEXT,
sent_email DATETIME,
created_at DATETIME,
updated_at DATETIME,
PRIMARY KEY (id),
FOREIGN KEY (user_id) REFERENCES users(id),
FOREIGN KEY (room_id) REFERENCES rooms(id));");

$conn->query("CREATE TABLE participants(
id INT NOT NULL AUTO_INCREMENT,
event_id INT NOT NULL,
email varchar(255) NOT NULL,
created_at DATETIME,
updated_at DATETIME,
access_way INT,
confirmed INT,
notes MEDIUMTEXT,
PRIMARY KEY (id),
FOREIGN KEY (event_id) REFERENCES events(id));");

$conn->query("CREATE TABLE lang (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    it TEXT,
    en TEXT,
    es TEXT,
    fr TEXT,
    de TEXT
);");

$conn->query("CREATE TABLE `logs` (
    `id` int NOT NULL AUTO_INCREMENT,
    `user_id` int DEFAULT NULL,
    `action_type` varchar(50) NOT NULL,
    `created_at` datetime DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `details` text,
    `level` int DEFAULT NULL,
    PRIMARY KEY (`id`)
)");

$conn->query("CREATE TABLE event_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    event_id INT NOT NULL,
    check_in DATETIME,
    check_out DATETIME,
    CONSTRAINT `event_participants` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
    FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`)
);");

User::create(
    [
        [
            "firstname" => "christian",
            "lastname" => "De Sica",
            "email" => "christiandesica@gmail.com",
            "username" => "christiandesica",
            "password" => sha1(md5('password')),
            "role" => 2,
            "enabled" => 1,
            "confirmed_at" => date('Y-m-d H:i:s')
        ],
        [
            "firstname" => "massimo",
            "lastname" => "Boldi",
            "email" => "massimoboldi@gmail.com",
            "username" => "massimoboldi",
            "password" => sha1(md5('password')),
            "role" => 1,
            "enabled" => 1,
            "confirmed_at" => date('Y-m-d H:i:s')
        ],
        [
            "firstname" => "alberto",
            "lastname" => "Sordi",
            "email" => "albertosordi@gmail.com",
            "username" => "albertosordi123",
            "password" => sha1(md5('password')),
            "role" => 1,
            "enabled" => 1,
            "confirmed_at" => date('Y-m-d H:i:s')
        ],
    ]
);

Mail_templates::create(
    [
        [
            "type" => "confirmation_account",
            "subject" => "Conferma la tua mail",
            "body" => "<h2 class=\"ql-align-center\">Benvenuto in Reservations_rooms</h2><p>Gentile {firstname} {lastname},</p><p>per poter accedere al tuo account, è necessaria l'attivazione del tuo account.</p><p><br></p><p>Per attivare l'account <a href=\"{confirmation_url}\" rel=\"noopener noreferrer\" target=\"_blank\">clicca qui!</a></p><p><br></p><p><strong>Di seguito, un riepilogo dei tuoi dati, conservali con cura e non condividerli con nessuno!</strong></p><p>Username: {username}</p><p>Email: {email}</p><p>Nome: {firstname}</p><p>Cognome: {lastname}</p><p><br></p><p class=\"ql-align-center\"><strong>{footer_disclaimer}</strong></p>",
            "enabled" => 1
        ],
        [
            "type" => "reset_password",
            "subject" => "Richiesta cambio password",
            "body" => "<h2 class=\"ql-align-center\">Recupera i tuoi dati</h2><p>Gentile {username},</p><p>è stato richiesto un cambio password.</p><p><br></p><p>Per modificare la tua password, <a href=\"{reset_password_url}\" rel=\"noopener noreferrer\" target=\"_blank\">clicca qui!</a></p><p><br></p><p><strong>Di seguito un riepilogo dei tuoi dati:</strong></p><p>Username: {username}</p><p>Email: {email}</p><p>Nome: {firstname}</p><p>Cognome: {lastname}</p><p><br></p><p class=\"ql-align-center\"><strong>{footer_disclaimer}</strong></p><p><br></p>",
            "enabled" => 1
        ],
        [
            "type" => "reservation_invite",
            "subject" => "Invito riunione {reservation_name}",
            "body" => "<h2 class=\"ql-align-center\">Sei stato invitato alla riunione: {reservation_name}</h2><p class=\"ql-align-center\"><br></p><p>Gentile utente, {reservation_creator} ti ha invitato alla sua riunione.</p><p>Inizio della riunione: {reservation_start}</p><p>Per confermare la tua partecipazione ed aggiungere eventuali note, <a href=\"{reservation_link}\" rel=\"noopener noreferrer\" target=\"_blank\">clicca qui!</a></p><p><br></p><p class=\"ql-align-center\"><strong>{footer_disclaimer}</strong></p>",
            "enabled" => 1
        ],
        [
            "type" => "reservation_start_alert",
            "subject" => "La tua riunione {reservation_name} inizia tra poco!",
            "body" => "<h2 class=\"ql-align-center\">La tua riunione {reservation_name} inizia tra poco!</h2><p>Gentile utente,</p><p>la tua riunione {reservation_name} inizierà tra {alert_time} minuti.</p><p><br></p><p>Questa è una mail automatica, si prega di non rispondere.</p><p><br></p><p class=\"ql-align-center\"><strong>{footer_disclaimer}</strong></p>",
            "enabled" => 1
        ],
        [
            "type" => "password_changed",
            "subject" => "Password modificata correttamente",
            "body" => "<h2 class=\"ql-align-center\"><strong>Password modificata correttamente</strong></h2><p>Gentile {firstname},</p><p>la tua password è stata modificata correttamente.</p><p><br></p><p>Se non sei stato tu a farlo, contatta immediatamente il supporto.</p><p><br></p><p>Questa email è stata inviata automaticamente, si prega di non rispondere.</p><p class=\"ql-align-center\"><strong>{footer_disclaimer}</strong></p>",
            "enabled" => 1
        ]
    ]
);

?>

