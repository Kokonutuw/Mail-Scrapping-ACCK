# 📥 Email Retrieval and Management Script - ACCK Internship Project

This project was developed during my internship at **ACCK**. Its goal is to create a PHP-based application that:

- Connects to an email inbox using the IMAP protocol,
- Retrieves all incoming emails,
- Extracts useful information (sender, subject, content, attachments),
- Stores everything in a MySQL database,
- And displays the emails via a simple web interface.

---

## 🧰 Technologies Used

- PHP (IMAP, PDO)
- MySQL
- HTML / CSS
- No external libraries (pure native PHP)

---

## 🗂 Project Structure

- `index.php`: Main interface — launches email retrieval and displays stored emails.
- `recupemail.php`: Handles IMAP connection and email parsing.
- `mail.php`: Displays detailed view of a specific email, including attachments.
- `attachments/` & `inline/`: Folders for saving downloaded files from emails.

---

## ⚙️ How It Works

1. The user clicks the **"Retrieve and Store Emails"** button.
2. The script connects to the email inbox using IMAP.
3. Emails are fetched and parsed (body, subject, sender, attachments).
4. Emails and associated data are stored in the database if not already present.
5. Processed emails are moved to the IMAP folder named **"Traitement"** (Processing).
6. All stored emails are displayed in an HTML table on the interface.

---

## 🔧 Configuration

- **IMAP login settings**: Edit in `recupemail.php` (`$hostname`, `$username`, `$password`).
- **MySQL database credentials**: Set in your DB connection file (e.g., `fonction.php`).
- Ensure `attachments/` and `inline/` folders are writable.

---
