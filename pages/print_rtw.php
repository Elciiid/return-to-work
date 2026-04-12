<?php
require_once __DIR__ . '/../connection/database.php';

// 1. Security Check
$is_authorized = $_SESSION['is_approver'] ?? false;

if (!isset($_SESSION['username']) || !$is_authorized) {
    die("Unauthorized access.");
}

include '../db/photo_helper.php';

// 2. Fetch the specific record
$id = $_GET['id'] ?? null;
if (!$id)
    die("No ID provided.");

try {
    $stmt = $conn->prepare("SELECT * FROM rtw_return_to_work WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data)
        die("Record not found.");
} catch (PDOException $e) {
    error_log("Print RTW Error: " . $e->getMessage());
    die("Database Error.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print RTW #<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
                padding: 0;
            }

            .print-container {
                border: none;
                box-shadow: none;
            }

            a:link:after,
            a:visited:after {
                content: "";
            }
        }

        @page {
            size: Letter;
            margin: 12mm;
        }
    </style>
</head>

<body class="bg-gray-100 p-10 font-sans">
    <div class="max-w-4xl mx-auto bg-white p-12 border border-gray-200 shadow-sm print-container">
        <div class="flex justify-between items-start border-b-2 border-gray-900 pb-6 mb-8">
            <div>
                <h1 class="text-2xl font-black uppercase">La Rose Noire</h1>
                <p class="text-sm font-bold text-gray-500">Facilities Management Department</p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-black text-pink-600">Return to Work Clearance</h2>
                <p class="text-xs font-bold text-gray-400">RTW NO: #<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-y-6 text-sm">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div
                        class="w-16 h-16 rounded-full overflow-hidden bg-pink-100 flex items-center justify-center shrink-0">
                        <?= getEmployeePhotoImg($data['employee_id'] ?? '', 'w-full h-full object-cover', htmlspecialchars($data['employee_name'] ?? '')) ?>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase">Employee Name</p>
                        <p class="font-bold text-lg"><?= htmlspecialchars($data['employee_name'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-black text-gray-400 uppercase">Employee ID</p>
                <p class="font-bold text-lg"><?= htmlspecialchars($data['employee_number'] ?? '') ?></p>
            </div>
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase">Assigned Area</p>
                <p class="font-bold"><?= htmlspecialchars($data['prodn_type'] ?? '') ?></p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-black text-gray-400 uppercase">Date Filed</p>
                <p class="font-bold"><?= date('M d, Y', strtotime($data['filing_date'])) ?></p>
            </div>
        </div>

        <div class="mt-10 bg-gray-50 p-6 rounded-xl border border-gray-200">
            <h3 class="text-xs font-black uppercase tracking-widest mb-4 text-gray-400">Absence Summary</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase">Days Absent</p>
                    <p class="font-black text-xl"><?= $data['days_absence'] ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase">Start Date</p>
                    <p class="font-bold"><?= date('M d, Y', strtotime($data['first_date_absence'])) ?></p>
                </div>
                <div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase">Return Date</p>
                    <p class="font-bold"><?= date('M d, Y', strtotime($data['date_returned'])) ?></p>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <p class="text-[10px] font-black text-gray-400 uppercase mb-2">Detailed Reason for Absence</p>
            <p class="text-gray-700 italic border-l-4 border-pink-200 pl-4 py-2 bg-pink-50/30">
                <?= nl2br(htmlspecialchars($data['reason'] ?? '')) ?>
            </p>
        </div>

        <div class="mt-8">
            <p class="text-[10px] font-black text-gray-400 uppercase mb-2">Nurse Declaration</p>
            <p class="text-gray-700 font-bold border-l-4 border-blue-200 pl-4 py-2 bg-blue-50/30">
                <?php
                $nurseDecl = $data['nurse_declaration'];
                $nurseReason = $data['nurse_reason'];
                if ($nurseDecl === null) {
                    echo 'Not Declared Yet';
                } else {
                    echo ucfirst($nurseDecl);
                    if ($nurseDecl === 'unfit to work' && !empty($nurseReason)) {
                        echo ' - Reason: ' . htmlspecialchars($nurseReason);
                    }
                }
                ?>
            </p>
        </div>
        <div class="mt-20 grid grid-cols-2 gap-20">
            <div class="text-center">
                <div class="min-h-[1.5rem] mb-1">
                    <p class="text-xs font-black uppercase"><?= htmlspecialchars($data['employee_name'] ?? '') ?></p>
                </div>
                <div class="border-t border-black pt-2">
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Employee Signature</p>
                </div>
            </div>

            <div class="text-center">
                <div class="min-h-[1.5rem] mb-1">
                    <p class="text-xs font-black uppercase">
                        <?= !empty($data['superior_name_position']) ? htmlspecialchars($data['superior_name_position']) : '' ?>
                    </p>
                </div>
                <div class="border-t border-black pt-2">
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Superior / Notified Personnel</p>
                </div>
            </div>
        </div>

        <div class="mt-12 text-center no-print space-y-3">
            <div class="text-xs text-gray-500 font-medium">
                For a clean print, disable “Headers and footers” in the print dialog.
            </div>
            <button onclick="window.print()"
                class="bg-pink-500 text-white px-8 py-3 rounded-full font-black uppercase text-xs tracking-widest hover:bg-pink-600 transition-all shadow-lg">
                <i class="fa-solid fa-print mr-2"></i> Print Document
            </button>
        </div>
    </div>
</body>

</html>
tml>