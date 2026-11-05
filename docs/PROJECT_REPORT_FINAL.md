# CIS 344 Group Project Report — Fall 2026
**Project:** BrightStart Childcare Center Management System  
**Student:** Mushfiqur Rahman  
**Professor:** Yanilda Peralta Ramos  
**Course:** CIS 344 — Fall 2026  
**College:** Lehman College, CUNY  
**Date:** November 4, 2026  
**Team members and contributions:** To be completed with actual group details.  
**Public GitHub URL:** Pending repository upload.  
**Demo video:** Pending recording or submission link.

## 1. Project plan and scope
BrightStart is an academic PHP/MySQL web application for a fictional childcare center. Its goal is to connect enrollment, guardian records, attendance, tuition billing, classroom occupancy, and staff scheduling in a consistent relational system. The scope includes creation and reporting for these workflows; advanced production administration is outside scope.

## 2. Technology and architecture
Browser HTML/CSS forms send POST requests to PHP (`index.php`), which validates requests and uses PDO prepared statements to interact with MySQL. The UI renders escaped database values. `config/database.php` stores connection configuration; `includes/bootstrap.php` contains reusable helpers. `assets/style.css` provides responsive styling. `sql/` contains database setup, fictional seed data, and five join queries.

## 3. Database design
Nine related tables: `classrooms`, `guardians`, `children`, `child_guardians`, `staff`, `shifts`, `attendance`, `invoices`, and `payments`. Foreign keys connect children to classrooms, children to guardians through the many-to-many junction, shifts to staff and classrooms, attendance and invoices to children, and payments to invoices. Primary keys, unique constraints, check constraints, and indexes support integrity and lookup performance.

## 4. SQL joins and their importance
**Enrollment:** children JOIN classrooms JOIN child_guardians JOIN guardians shows which adults are associated with each child. **Attendance:** attendance JOIN children JOIN classrooms connects check-in data to the assigned room. **Billing:** invoices JOIN children LEFT JOIN payments with `SUM` and `COALESCE` calculates remaining balances, including unpaid invoices. **Scheduling:** shifts JOIN staff JOIN classrooms shows assignments. **Capacity:** classrooms LEFT JOIN active children and `COUNT` retains empty rooms in occupancy reporting. Full SQL examples are in `sql/joins.sql`.

## 5. Key implementation decisions
Enrollment uses a transaction and `SELECT ... FOR UPDATE` to lock a classroom row before checking current active enrollment, reducing concurrent overbooking risk. Billing uses a transaction and locked invoice row before rejecting overpayments. Duplicate attendance entries are updated by unique child/date key. PDO prepared statements reduce SQL injection risk, and HTML output is escaped.

## 6. Security and limitations
Includes CSRF token checks, prepared SQL, server-side field validation, output escaping, and safe generic database errors. This is a classroom prototype and **must not be used with real child information**: no login/authorization, audit log, encryption-at-rest, or compliance review is provided.

## 7. Testing and results
The MySQL schema, sample data, and five SQL join queries were executed successfully in MySQL Workbench. PHP was installed and its local development server started, but end-to-end website testing was blocked by a PHP/MySQL authentication compatibility error. See `docs/TEST_PLAN.md` for remaining application tests; add dated screenshots and actual results when verified.

## 8. Development process and teamwork
Project stages include schema design, UI, enrollment, attendance, billing, staff scheduling, integration, testing, and documentation. Actual group member contributions, communication practices, and Git commit history must be documented before final submission.

## 9. User guide and demo
See `docs/USER_GUIDE.md` for step-by-step use and `docs/DEMO_SCRIPT.md` for a real screen-recorded demonstration. Actual application screenshots and a demo link are still required before final submission.

## 10. Conclusion
The database implementation demonstrates relational data management with meaningful joins across the core childcare workflows. The PHP application source is prepared, but a working end-to-end connection remains to be verified. Further work could include user authentication, roles, audit logging, and hardened deployment.
