# La Rose Noire - Return to Work Portal Documentation

## Project Overview
The **Return to Work Portal** is a web-based application designed for **La Rose Noire** to streamline the process of employees returning to work after an absence. It eliminates manual paper forms by providing a digital workflow for filing, supervisor approval, and nurse health clearance.

## Key Features

### 1. Employee Self-Service (User Portal)
-   **Digital Filing**: Employees can file a "Return to Work" application directly from the web interface.
-   **Automated Autofill**: The system automatically pulls the logged-in user's details (Name, ID, Department).
-   **Attachment Support**: Users can upload medical certificates (PDF, Images).
-   **Supervisor Selection**: Dynamic lookup for supervisors based on department.
-   **Status Tracking**: Real-time view of application status (Pending, Approved, etc.).

### 2. Supervisor / Approver Portal
-   **Approvals Dashboard**: Supervisors can view all pending applications from their department.
-   **Review Process**: Ability to Approve or Decline "Return to Work" requests.
-   **Notification Badge**: Visual indicator for the number of pending requests.

### 3. Nurse / Health Portal
-   **Health Clearance**: Nurses can review approved return-to-work requests.
-   **Declaration**: Ability for nurses to input remarks and clear employees for work.
-   **Dashboard**: A dedicated view for clinic staff to manage daily clearances.

### 4. System & Security
-   **Authentication**: Secure login system with role-based access control.
-   **Role Switching**: (Dev/Admin) Ability to switch roles for testing purposes.
-   **Responsive Design**: Mobile-friendly interface optimized for all devices.

## Technology Stack

-   **Backend**: PHP (Vanilla)
-   **Frontend**: HTML5, Tailwind CSS (via CDN), JavaScript
-   **Database**: MySQL / MariaDB
-   **Server**: Apache (via XAMPP)

## Folder Structure

```
/
├── auth/                   # Authentication logic (Login, Logout, Role Switching)
│   ├── login.php           # Login Page
│   ├── authenticate.php    # Auth Processing
│   └── logout.php          # Session Destruction
├── db/                     # Database Connection & Helper Scripts
│   ├── db.php              # Database Connection
│   ├── submit.php          # Form Submission Handler
│   └── get_supervisors.php # API to fetch supervisors
├── pages/                  # Main Application Views
│   ├── index.php           # Main User Dashboard / Application Form
│   ├── approvals.php       # Supervisor Approval Interface
│   ├── nurse_dashboard.php # Nurse Overview
│   ├── history.php         # Application History
│   └── dashboard.php       # General Dashboard
├── uploads/                # Directory for uploaded Medical Certificates
├── style.css               # Global Styles
└── index.php               # (Often redirects to pages/ or auth/)
```

## Database Schema (Key Tables)

### `return_to_work`
Stores the main application data.
-   `id`: Primary Key
-   `employee_id`: Unique Employee ID
-   `employee_name`: Full Name
-   `department`: Department
-   `date`: Submission Date
-   `status`: 'Pending', 'Approved', 'Declined', etc.
-   `medical_certificate_path`: Path to uploaded file

## Installation & Setup

1.  **Prerequisites**:
    -   XAMPP (or similar PHP/MySQL environment) installed.
    -   Database imported (ensure `db/db.php` credentials match).

2.  **Configuration**:
    -   Update `db/db.php` with your local database credentials if necessary.

3.  **Running the App**:
    -   Place the project folder in `htdocs`.
    -   Navigate to `http://localhost/return-to-work-final/` in your browser.

## Workflow

1.  **Login**: User logs in with their credentials.
2.  **Submission**: User fills out `pages/index.php` and submits.
3.  **Supervisor Review**: Supervisor logs in, sees the request in `pages/approvals.php`, and approves it.
4.  **Nurse Clearance**: Nurse sees the approved employee in their dashboard and performs final health clearance.
5.  **Completion**: The record is marked as complete/returned.
