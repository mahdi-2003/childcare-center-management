USE childcare_center;
-- 1: Children, classrooms, and guardians (many-to-many junction)
SELECT c.full_name AS child, cl.name AS classroom, g.full_name AS guardian, cg.relationship
FROM children c JOIN classrooms cl ON cl.id=c.classroom_id
JOIN child_guardians cg ON cg.child_id=c.id JOIN guardians g ON g.id=cg.guardian_id;
-- 2: Attendance plus classroom
SELECT a.attendance_date,c.full_name,cl.name AS classroom,a.check_in,a.check_out
FROM attendance a JOIN children c ON c.id=a.child_id JOIN classrooms cl ON cl.id=c.classroom_id;
-- 3: Invoice balances, including invoices without payments
SELECT i.id,c.full_name,i.amount,COALESCE(SUM(p.amount),0) AS paid,
 i.amount-COALESCE(SUM(p.amount),0) AS balance
FROM invoices i JOIN children c ON c.id=i.child_id LEFT JOIN payments p ON p.invoice_id=i.id
GROUP BY i.id,c.full_name,i.amount;
-- 4: Staff shifts and classrooms
SELECT s.full_name,cl.name,sh.shift_date,sh.start_time,sh.end_time
FROM shifts sh JOIN staff s ON s.id=sh.staff_id JOIN classrooms cl ON cl.id=sh.classroom_id;
-- 5: Classroom occupancy (LEFT JOIN retains empty rooms)
SELECT cl.name,cl.capacity,COUNT(c.id) AS enrolled
FROM classrooms cl LEFT JOIN children c ON c.classroom_id=cl.id AND c.status='active'
GROUP BY cl.id,cl.name,cl.capacity;
