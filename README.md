## ER Diagram – Relationship Cardinalities

The ER diagram illustrates the relationships between the main entities of the Library Management System. The following cardinalities describe these relationships:

| Relationship | Cardinality | Description |
|---|---|---|
| User – Loan | 1:N | One user can have multiple loans. Each loan belongs to one user. |
| Book – Loan | 1:N | One book can be associated with multiple loan records over time. Each loan refers to one book. |
| Loan – Penalty | 1:0..1 | A loan can have zero or one penalty. Each penalty belongs to one loan. |
| User – Notification | 1:N | One user can receive multiple notifications. Each notification belongs to one user. |
| User – SystemLog | 1:N | One user can generate multiple system log records. Each log record is associated with one user. |
| User – Payment (payer) | 1:N | One user can make multiple payments. Each payment is associated with one payer. |
| User – Payment (processed_by) | 1:N | One operator or administrator can process multiple payments. A payment may have one assigned processor or none. |
| Loan – Payment | 1:N | One loan can have multiple payment records. A payment can be associated with one loan or none. |
| Penalty – Payment | 1:N | One penalty can have multiple payment records. A payment can be associated with one penalty or none. |

**Note:** The `Payment` entity can be associated with either a `Loan` or a `Penalty`, but not both at the same time. The `SystemSetting` entity is independent and does not have a direct relationship with other entities.n