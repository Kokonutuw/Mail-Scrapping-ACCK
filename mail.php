<?php
include 'functions.php';

$pdo = connect_to_db(); // Connexion à la base de données

// Récupérer les emails depuis la base de données
$query = "SELECT emails.*, tickets.subject AS ticket_subject FROM emails
          LEFT JOIN tickets ON emails.ticket_id = tickets.ticket_id
          ORDER BY emails.date_sent DESC";
$emails = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Affichage des emails dans un tableau
echo "<table border='1'>";
echo "<tr><th>UID</th><th>Date</th><th>Expéditeur</th><th>Sujet de l'email</th><th>Sujet du ticket</th></tr>";

foreach ($emails as $email) {
    echo "<tr>";
    echo "<td><a href='mail.php?uid=" . $email['uid'] . "'>" . $email['uid'] . "</a></td>";
    echo "<td>" . $email['date_sent'] . "</td>";
    echo "<td>" . $email['from_email'] . "</td>";
    echo "<td>" . $email['subject'] . "</td>";
    echo "<td>" . ($email['ticket_subject'] ? $email['ticket_subject'] : "Aucun ticket associé") . "</td>";
    echo "</tr>";
}

echo "</table>";

if (isset($_GET['uid'])) {
    $uid = $_GET['uid'];
    $stmt = $pdo->prepare("SELECT * FROM emails WHERE uid = ?");
    $stmt->execute([$uid]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($email) {
        echo "<h2>Email: " . $email['uid'] . "</h2>";
        echo "<p><strong>Date:</strong> " . $email['date_sent'] . "</p>";
        echo "<p><strong>De:</strong> " . $email['from_email'] . "</p>";
        echo "<p><strong>Sujet:</strong> " . $email['subject'] . "</p>";
        echo "<div><strong>Contenu de l'email:</strong><br>" . $email['body'] . "</div>";

        $stmt = $pdo->prepare("SELECT * FROM attachments WHERE email_uid = ?");
        $stmt->execute([$uid]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($attachments) {
            echo "<h3>Pièces jointes :</h3><ul>";
            foreach ($attachments as $attachment) {
                echo "<li><a href='" . $attachment['file_path'] . "'>" . $attachment['filename'] . "</a></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "Email non trouvé.";
    }
}
?>
