# Test plan — execute and record actual results
| Test | Procedure | Expected result |
|---|---|---|
| Database import | Run schema then seed | Nine tables, sample records |
| Dashboard | Open app | Counts and classroom report visible |
| Guardian | Add valid unique guardian | Saved; selectable in enrollment |
| Duplicate guardian | Reuse email | Safe error; no duplicate |
| Enrollment | Add child to available room | Child appears with guardian and room |
| Capacity | Fill a small classroom to its limit, then add one more | Rejected without partial insert |
| Attendance | Add check-in then update checkout for same child/date | One attendance row with updated checkout |
| Invalid checkout | Checkout earlier than check-in | Rejected |
| Billing | Create invoice, pay part | Remaining balance correctly calculated |
| Overpayment | Pay more than outstanding | Rejected |
| Staff | Add staff and schedule valid shift | Joined shift report shows staff and room |
| Invalid shift | End before start | Rejected |
| Injection | Try SQL text in form fields | Treated as data, not SQL |
| HTML injection | Input `<script>alert(1)</script>` as a name | Rendered as text; no execution |
| Mobile | Resize browser narrow | Forms and navigation remain usable; tables scroll |
| SQL joins | Run five SELECTs in joins.sql | Correct related data, empty rooms retained |

Mark actual PASS/FAIL and capture screenshots. Do not claim unperformed tests passed.
