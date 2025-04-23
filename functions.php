<?php

//Fonction pour ce connecter à la BDD
function connect_to_db() {
    $host = '';
    $db = '';
    $user = '';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Erreur de connexion à la base de données : ' . $e->getMessage());
    }
}

// Fonction pour récupérer un ticket existant par sujet
function get_ticket_id_by_subject($subject, $pdo) {
    $stmt = $pdo->prepare("SELECT ticket_id FROM tickets WHERE `subject` = ?");
    $stmt->execute([$subject]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    return $ticket ? $ticket['ticket_id'] : null;
}

// Fonction pour insérer un email et l'associer à un ticket
function insert_email($uid, $from, $subject, $body, $date_sent, $pdo) {
    // Vérifier si l'email existe deja
    $stmt = $pdo->prepare("SELECT uid FROM emails WHERE uid = ?"); 
    $stmt->execute([$uid]);
    if ($stmt->fetch()) return false;

    // Vérifie si un ticket avec ce sujet existe deja
    $ticket_id = get_ticket_id_by_subject($subject, $pdo);

    if (!$ticket_id) {
        $stmt = $pdo->prepare("INSERT INTO tickets (subject) VALUES (?)");
        $stmt->execute([$subject]);
        $ticket_id = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("INSERT INTO emails (uid, from_email, subject, body, date_sent, ticket_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$uid, $from, $subject, $body, $date_sent, $ticket_id]);
    return $ticket_id;
}

// Fonction pour insérer une pièce jointe
function insert_attachment($email_uid, $filename, $file_path, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO attachments (email_uid, filename, file_path) VALUES (?, ?, ?)");
    $stmt->execute([$email_uid, $filename, $file_path]);
}

// Fonction pour décoder le corps de l'email

function decode_body($data, $encoding) {
    switch ($encoding) {
        case 0: return $data; // Pas d'encodage
        case 1: return imap_qprint($data); // Quoted-Printable
        case 2: return imap_binary($data);
        case 3: return imap_base64($data); // Base64
        case 4: return quoted_printable_decode($data);
        default: return $data;
    }
}