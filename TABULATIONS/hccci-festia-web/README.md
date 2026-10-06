# HCCCI FesTia 2026 — PHP + MySQL

## Install with XAMPP
1. Extract `hccci-festia-web` into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Open `http://localhost/phpmyadmin`, choose **Import**, and import `database/schema.sql`. This creates the `hccci_festia` database and all tables.
4. Edit `config.php` to match your MySQL host, port, database, username and password. The bundled values are only a local XAMPP example; create a dedicated restricted MySQL user for shared or production use.
5. Confirm `pdo_mysql` is enabled in PHP, then visit `http://localhost/hccci-festia-web/`. Register the first administrator and create named encoder accounts in Admin.
6. Open **Admin → Audit Logs** to inspect score edits, gold awards, sign-ins and administrator actions. The Overall Gold Tally uses the `gold_awards` table.

## Upgrading from the SQLite version
**Do not delete your existing SQLite database.** This package starts with empty MySQL tables. Previous users, scores, gold awards and audit history are **not automatically migrated**. Keep a backup of `storage/festia.sqlite` and migrate its data separately if needed. The existing event and IRR data remains in `data/events.json`.

## Security and operational notes
- Store MySQL credentials outside the web root when deploying publicly. Never commit `config.php`.
- Use HTTPS, unique user accounts and regular MySQL backups.
- Audit records are inserted in the same database transaction as score and gold changes; login audit entries are separate.
- The SHA-256 hash chain is tamper-evident only against casual edits, not a privileged database administrator. For strong audit guarantees, ship logs to an independent write-only destination.
- For a shared event server, test concurrent writes and all score/award workflows on your actual PHP/MySQL versions before official use.
- Source IRR entries cover the extracted event set; verify rubrics against the signed IRR before official scoring.
