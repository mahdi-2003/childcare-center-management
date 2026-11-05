# BrightStart Childcare Center Management System
CIS 344 · Fall 2026 · Lehman College · Group Project

Student: **Mushfiqur Rahman** · Professor: **Yanilda Peralta Ramos**

## Stack
PHP 8.1+, MySQL 8+, HTML5, CSS3. PDO prepared statements. No external packages.

## Quick start (Mac using MAMP, or any PHP/MySQL setup)
1. Start Apache/PHP and MySQL (MAMP, XAMPP, or Homebrew equivalents).
2. Import `sql/schema.sql`, then `sql/seed.sql` using MySQL Workbench (File > Open SQL Script > lightning bolt) or `mysql -u root -p < sql/schema.sql` and `mysql -u root -p < sql/seed.sql`.
3. Configure `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` as environment variables for your local server, or edit `config/database.php` locally. **Never commit real passwords.** MAMP defaults can differ (port 8889 for MySQL); configure the host/port accordingly.
4. Put the folder in your web server document root, then visit `http://localhost/childcare_project/index.php`. Alternatively, from this folder run `php -S localhost:8000` and open `http://localhost:8000` (MySQL must still be running).
5. Open `sql/joins.sql` in Workbench and execute each SELECT to show database relationships.

## Feature workflow
Classrooms → Guardians → Children → Attendance → Invoices → Payments → Staff → Shifts. The dashboard shows totals. Enrollment uses a transaction and row lock to reject over-capacity classrooms. Payments use a transaction to reject overpayment.

## Safety and scope
Academic prototype with **fictional records only**. CSRF tokens, HTML escaping, server-side validation, prepared statements, and transactional checks are implemented. Authentication, role-based access, audit trails, encryption at rest, secure production deployment, and regulatory compliance are NOT implemented. Do not expose publicly or store real child data.

## SQL joins
`sql/joins.sql` includes five annotated JOIN reports: child/guardian/classroom; attendance/classroom; invoices/payments; staff/shifts/classrooms; classroom occupancy. The UI displays each related report.

## GitHub instructions
Create a new **public** GitHub repository, upload these files (not secrets), add the repository URL to your final PDF, and commit incrementally with meaningful messages. Suggested history: `Create database schema`, `Implement enrollment and capacity checks`, `Add attendance and billing`, `Add staff scheduling and dashboard`, `Document testing and demo`. Do not fabricate commits; actually commit as you work.

## Team and evaluation
Record each person's real contribution in the report. Capture screenshots from the running app, record a real demonstration video, and replace report placeholders. Test using `docs/TEST_PLAN.md`.

## Verification status (November 4, 2026)
The database schema, seed data and five JOIN queries were executed in MySQL Workbench. The PHP frontend has **not** been verified end-to-end because the local PHP MySQL driver could not authenticate against MySQL 9.5. See `docs/PROJECT_REPORT_FINAL.pdf` for details. Do not claim the website works until retested.
