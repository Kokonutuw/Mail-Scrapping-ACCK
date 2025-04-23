

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupération et Stockage des Mails</title>
</head>
<body>
    <h1>Bienvenue dans l'application de récupération des mails</h1>

    <form method="POST">
        <button type="submit" name="recup_mail">Récupérer et Stocker les Emails</button>
    </form>

</body>
</html>


<?php
include('recupemail.php'); 
include('../db_connexion/db_connexion.php');


$pdo = getDbConnexion();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recup_mail'])) {


    $emails = recupmail();


    if (is_array($emails)) {
        foreach ($emails as $emails_contents) { 
            if (!isset($emails_contents["uid"]) || empty($emails_contents["uid"])) { 
                echo " Erreur : UID est manquant pour cet email !<br>";
                continue;
            }

            // Vérification correcte de l'UID
            $uid = $emails_contents["uid"];
            $email_from = $emails_contents["email_from"] ;
            $subject = $emails_contents["subject"] ;
            $date = $emails_contents["date"] ;
            $udate = $emails_contents["udate"] ;
            $rez = $emails_contents["REZ"] ;
            $attachments = json_encode($emails_contents["attachments"] ?? []);
            $name_attachments = json_encode($emails_contents["name_attachments"] ?? []);
            $inline_images = json_encode($emails_contents["inline_images"] ?? []);

            // Insertion en BDD 
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM emails WHERE uid = :uid");
            $stmt->execute(['uid' => $uid]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                $stmt = $pdo->prepare("
                    INSERT INTO emails (uid, email_from, subject, date, udate, REZ, attachments, name_attachments, inline_images)
                    VALUES (:uid, :email_from, :subject, :date, :udate, :rez, :attachments, :name_attachments, :inline_images)
                ");
                $stmt->execute([
                    ':uid' => $uid,
                    ':email_from' => $email_from,
                    ':subject' => $subject,
                    ':date' => $date,
                    ':udate' => $udate,
                    ':rez' => $rez,
                    ':attachments' => $attachments,
                    ':name_attachments' => $name_attachments,
                    ':inline_images' => $inline_images
                ]);
            } else {
                echo "<p>Email UID: $uid déjà présent en BDD.</p>";
            }
        }

    } else {
        echo " Erreur : la fonction recupmail() ne retourne pas un tableau valide.";
        var_dump($emails);
    }

}

$request = 'SELECT * FROM emails ORDER BY date DESC';
$stmt = $pdo->prepare($request);
$stmt->execute();
$emails_from_db = $stmt->fetchAll();



echo "<table border='1'>";
echo "<tr><th>UID</th><th>Date</th><th>Expéditeur</th><th>Sujet de l'email</th><th>Sujet du ticket</th></tr>";

foreach ($emails_from_db as $email) {
    echo "<tr>";
    echo "<td><a href='?uid=" . $email['uid'] . "'>" . $email['uid'] . "</a></td>";
    echo "<td>" . $email['date'] . "</td>";
    echo "<td>" . $email['email_from'] . "</td>";
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
        echo "<p><strong>Contenu de l'email:" . $email['REZ'] . "</p>";

        $stmt = $pdo->prepare("SELECT * FROM attachments WHERE email_uid = ?");
        $stmt->execute([$uid]);
        $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
