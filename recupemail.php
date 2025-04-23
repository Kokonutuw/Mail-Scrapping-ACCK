<?php


function decode_body($data, $encoding) {
    switch ($encoding) {
        case 0: return mb_convert_encoding(quoted_printable_decode($data), 'UTF-8', 'Windows-1252');
        case 1: return imap_qprint($data);
        case 2: return imap_binary($data);
        case 3: return imap_base64($data);
        case 4: return quoted_printable_decode($data);
        default: return $data;
    }
}

function recupmail() {
    $hostname = '{}INBOX';
    $username = '';
    $password = '';

    $inbox = imap_open($hostname, $username, $password) or die('Échec de connexion : ' . imap_last_error());

    $emails_ids = imap_search($inbox, 'ALL');
    if (!$emails_ids) {
        imap_close($inbox);
        return [];
    }

    $all_emails_contents = [];

    foreach ($emails_ids as $email_number) {
        $uid = imap_uid($inbox, $email_number);
        $header = imap_headerinfo($inbox, $email_number);
        $from = $header->from[0]->mailbox . "@" . $header->from[0]->host;
        $subject = imap_utf8($header->subject ?? '(Sans sujet)');
        $date_sent = $header->MailDate;
        $udate_sent = $header->udate;

        $date_sent = preg_replace('/ [\+\-]\d{4}$/', '', $date_sent);

        // date en format SQL (robuste)
        $date_obj = DateTime::createFromFormat('d-M-Y H:i:s', $date_sent);
        $date_sql = $date_obj ? $date_obj->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($date_sent));
        


        $structure = imap_fetchstructure($inbox, $email_number);
        $encoding = isset($structure->parts[0]) ? $structure->parts[0]->encoding : 0;

        $body = imap_fetchbody($inbox, $email_number, 1.2);
        $encoding_source = "1.2";

        if (empty($body)) {
            $body = imap_fetchbody($inbox, $email_number, 2);
            $encoding_source = "2";
        }

        $rez = "";
        $case = "";
        if (!empty($body)) {
            if (preg_match('/(charset=3DWindows)/', $body)) {
                $case = "CAS A.A - get $encoding_source - charset Windows defined force 0";
                $rez = decode_body($body, 0);
            } elseif (preg_match('/(charset=3Diso-8859)/', $body)) {
                $case = "CAS A.A2 - get $encoding_source - charset iso8859 defined force 0";
                $rez = decode_body($body, 0);
            } else {
                $case = "CAS A.A2 - get $encoding_source - default 4";
                $rez = decode_body($body, 4);
            }

            if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $rez)) {
                $case = "CAS A.C - get $encoding_source - encode force 3";
                $rez = decode_body($body, 3);
            }
        } else {
            $case = "CAS B.B - Aucun body récupéré";
            $rez = "";
        }

        $attachments = [];
        $inline_images = [];
        $name_attachments = [];

        if (isset($structure->parts)) {
            foreach ($structure->parts as $partno => $part) {
                $attachment_data = imap_fetchbody($inbox, $email_number, $partno + 1);
                $attachment_data = decode_body($attachment_data, $part->encoding);

                $filename = isset($part->dparameters[0]->value) ? $part->dparameters[0]->value : 'attachment';
                $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
                $file_base_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $subject);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $exist = 1;
                while (file_exists('attachments/' . $file_base_name . '_' . $exist . '.' . $extension)) {
                    $exist++;
                }
                $new_filename = $file_base_name . '_' . $exist . '.' . $extension;

                if (isset($part->disposition) && strtoupper($part->disposition) === 'ATTACHMENT') {
                    $file_path = 'attachments/' . $new_filename;
                    file_put_contents($file_path, $attachment_data);
                    $attachments[] = $new_filename;
                } elseif (!empty($part->id)) {
                    $content_id = trim($part->id, "<>");
                    $ext = isset($part->subtype) ? strtolower($part->subtype) : 'jpg';
                    $file_path = "inline/" . md5($content_id) . "." . $ext;
                    file_put_contents($file_path, $attachment_data);
                    $inline_images[$content_id] = $file_path;
                } elseif (isset($part->disposition) && strtoupper($part->disposition) === 'INLINE' && empty($part->id)) {
                    $file_path = 'attachments/' . $new_filename;
                    file_put_contents($file_path, $attachment_data);
                    $name_attachments[] = $new_filename;
                }
            }
        }

        $base_url = "https://intranet.acck.fr/tickets/";
        if (!empty($rez)) {
            $rez = preg_replace_callback('/src=["\']cid:([^"\']+)["\']/i', function($matches) use ($inline_images, $base_url) {
                return isset($inline_images[$matches[1]]) ? 'src="'.$base_url.$inline_images[$matches[1]].'"' : $matches[0];
            }, $rez);
        }


        $all_emails_contents[] = [
            "uid" => $uid,
            "email_from" => $from,
            "subject" => $subject,
            "date" => $date_sql,
            "udate" => $udate_sent,
            "REZ" => $rez,
            "attachments" => $attachments,
            "name_attachments" => $name_attachments,
            "inline_images" => $inline_images
        ];
        imap_mail_move($inbox, $email_number, 'Traitement');
    }
    imap_expunge($inbox);

    imap_close($inbox);
    return $all_emails_contents;
}

