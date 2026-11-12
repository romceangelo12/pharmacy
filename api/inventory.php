<?php
/**
 * REST API - Inventory Data (Supports MySQL + Hospital CSV Fallback)
 * Pharmacy Inventory System
 */

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$pdo = getDBConnection();
$stockType = trim($_GET['stock_type'] ?? 'all');
$search = strtolower(trim($_GET['search'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please sign in before adding inventory.']);
        exit;
    }

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Please ensure MySQL is started.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
        exit;
    }

    $name = trim($input['name'] ?? '');
    $genericName = trim($input['generic_name'] ?? '');
    $stockTypeInput = trim($input['stock_type'] ?? 'regular');
    $unitOfMeasure = trim($input['unit_of_measure'] ?? 'Tablet');
    $batchNumber = trim($input['batch_number'] ?? '');
    $unitPrice = $input['unit_price'] ?? '';
    $dpriPrice = $input['dpri_price'] ?? '';
    $categoryName = trim($input['category_name'] ?? '');
    $otherCategory = trim($input['other_category'] ?? '');
    $beginningBalance = filter_var($input['beginning_balance'] ?? null, FILTER_VALIDATE_INT);
    $monthlyConsumption = filter_var($input['monthly_consumption'] ?? null, FILTER_VALIDATE_INT);
    $deliveryDate = trim($input['delivery_date'] ?? '');
    $qtyDelivered = trim($input['qty_delivered'] ?? '');
    $dailyInput = $input['daily_consumption'] ?? null;
    $specialInput = $input['special_entries'] ?? null;
    $quantity = filter_var($input['quantity'] ?? null, FILTER_VALIDATE_INT);
    $minAlertQty = filter_var($input['min_alert_qty'] ?? null, FILTER_VALIDATE_INT);
    $expiryInput = trim($input['expiry_date'] ?? '');
    $expiryDate = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryInput);

    if ($name === '' || strlen($name) > 150) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Item name is required and must be 150 characters or fewer.']);
        exit;
    }
    if (strlen($genericName) > 150 || strlen($batchNumber) > 50) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Generic name or lot number is too long.']);
        exit;
    }
    if (strlen($categoryName) > 100 || strlen($otherCategory) > 150 || strlen($deliveryDate) > 100 || strlen($qtyDelivered) > 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A category or delivery field is too long.']);
        exit;
    }
    if (!in_array($stockTypeInput, ['regular', 'donation', 'mhmap'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Choose a valid stock program.']);
        exit;
    }
    if ($unitOfMeasure === '' || strlen($unitOfMeasure) > 50) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unit of measure is required and must be 50 characters or fewer.']);
        exit;
    }
    if (!is_numeric($unitPrice) || (float)$unitPrice < 0 || (float)$unitPrice > 99999999.99) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Enter a valid non-negative unit price.']);
        exit;
    }
    if (!is_numeric($dpriPrice) || (float)$dpriPrice < 0 || (float)$dpriPrice > 99999999.99) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Enter a valid non-negative DPRI price.']);
        exit;
    }
    if ($quantity === false || $quantity < 0 || $minAlertQty === false || $minAlertQty < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Quantity and minimum alert quantity must be non-negative whole numbers.']);
        exit;
    }
    if ($beginningBalance === false || $beginningBalance < 0 || $monthlyConsumption === false || $monthlyConsumption < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Beginning balance and monthly consumption must be non-negative whole numbers.']);
        exit;
    }
    if (!is_array($dailyInput) || count($dailyInput) !== 30 || !is_array($specialInput)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Enter all daily and special-column values.']);
        exit;
    }
    $dailyConsumption = [];
    foreach (array_values($dailyInput) as $amount) {
        if ($amount === '' || $amount === null) {
            $dailyConsumption[] = null;
            continue;
        }
        $validatedAmount = filter_var($amount, FILTER_VALIDATE_INT);
        if ($validatedAmount === false || $validatedAmount < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Daily consumption must be non-negative whole numbers.']);
            exit;
        }
        $dailyConsumption[] = $validatedAmount;
    }
    $specialEntries = [];
    foreach (['sl-return', 'lgu-obando', 'inv', 'sl-ret'] as $column) {
        $amount = $specialInput[$column] ?? '';
        if ($amount === '' || $amount === null) {
            $specialEntries[$column] = null;
            continue;
        }
        $validatedAmount = filter_var($amount, FILTER_VALIDATE_INT);
        if ($validatedAmount === false || $validatedAmount < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Special-column values must be non-negative whole numbers.']);
            exit;
        }
        $specialEntries[$column] = $validatedAmount;
    }
    if (!$expiryDate || $expiryDate->format('Y-m-d') !== $expiryInput) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Enter a valid expiry date.']);
        exit;
    }

    $expiryDateValue = $expiryDate->format('Y-m-d');
    if ($expiryDateValue < (new DateTimeImmutable('today'))->format('Y-m-d')) {
        $status = 'expired';
    } elseif ($quantity === 0) {
        $status = 'out_of_stock';
    } elseif ($quantity <= $minAlertQty) {
        $status = 'critical_stock';
    } else {
        $status = 'in_stock';
    }

    $weeklyConsumption = [];
    foreach ([
        '1-7' => [1, 7],
        '8-14' => [8, 14],
        '15-21' => [15, 21],
        '22-28' => [22, 28],
        '29-30' => [29, 30]
    ] as $week => [$startDay, $endDay]) {
        $weeklyConsumption[$week] = array_sum(array_map(
            static function ($amount) { return $amount ?? 0; },
            array_slice($dailyConsumption, $startDay - 1, $endDay - $startDay + 1)
        ));
    }
    $reportData = json_encode([
        'dpri_price' => (float)$dpriPrice,
        'category_name' => $categoryName,
        'other_category' => $otherCategory,
        'beginning_balance' => $beginningBalance,
        'delivery_date' => $deliveryDate,
        'qty_delivered' => $qtyDelivered,
        'monthly_consumption' => $monthlyConsumption,
        'daily_consumption' => $dailyConsumption,
        'special_entries' => $specialEntries,
        'weekly_consumption' => $weeklyConsumption
    ], JSON_UNESCAPED_UNICODE);

    $prefix = ['regular' => 'REG', 'donation' => 'DON', 'mhmap' => 'MHM'][$stockTypeInput];
    do {
        $sku = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
        $checkSku = $pdo->prepare('SELECT 1 FROM medicines WHERE sku = :sku LIMIT 1');
        $checkSku->execute(['sku' => $sku]);
    } while ($checkSku->fetchColumn());

    try {
        $hasReportDataColumn = (bool)$pdo->query("SHOW COLUMNS FROM medicines LIKE 'report_data'")->fetch();
        if (!$hasReportDataColumn) {
            $pdo->exec('ALTER TABLE medicines ADD COLUMN report_data LONGTEXT NULL');
        }
        $hasUnitColumn = (bool)$pdo->query("SHOW COLUMNS FROM medicines LIKE 'unit_of_measure'")->fetch();
        $statusColumn = $pdo->query("SHOW COLUMNS FROM medicines LIKE 'status'")->fetch();
        $statusType = $statusColumn['Type'] ?? '';
        if ($status === 'critical_stock' && strpos($statusType, "'critical_stock'") === false && strpos($statusType, "'low_stock'") !== false) {
            $status = 'low_stock';
        }

        if ($hasUnitColumn) {
            $stmt = $pdo->prepare("
                INSERT INTO medicines
                    (sku, name, generic_name, stock_type, batch_number, dosage_form, unit_of_measure, unit_price, quantity, min_alert_qty, expiry_date, status, report_data)
                VALUES
                    (:sku, :name, :generic_name, :stock_type, :batch_number, :dosage_form, :unit_of_measure, :unit_price, :quantity, :min_alert_qty, :expiry_date, :status, :report_data)
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO medicines
                    (sku, name, generic_name, stock_type, batch_number, dosage_form, unit_price, quantity, min_alert_qty, expiry_date, status, report_data)
                VALUES
                    (:sku, :name, :generic_name, :stock_type, :batch_number, :dosage_form, :unit_price, :quantity, :min_alert_qty, :expiry_date, :status, :report_data)
            ");
        }

        $params = [
            'sku' => $sku,
            'name' => $name,
            'generic_name' => $genericName !== '' ? $genericName : null,
            'stock_type' => $stockTypeInput,
            'batch_number' => $batchNumber !== '' ? $batchNumber : null,
            'dosage_form' => $unitOfMeasure,
            'unit_price' => (float)$unitPrice,
            'quantity' => $quantity,
            'min_alert_qty' => $minAlertQty,
            'expiry_date' => $expiryDateValue,
            'status' => $status,
            'report_data' => $reportData
        ];
        if ($hasUnitColumn) {
            $params['unit_of_measure'] = $unitOfMeasure;
        }

        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Inventory item added successfully.', 'sku' => $sku]);
    } catch (PDOException $e) {
        error_log('Inventory item creation failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to save the inventory item. Please try again.']);
    }
    exit;
}

// Function to load from CSV directly as reliable source
function loadFromCSV($stockType, $search) {
    $csvFile = __DIR__ . '/../data/hospital_inventory.csv';
    if (!file_exists($csvFile)) {
        return ['stats' => ['total' => 0, 'regular' => 0, 'donation' => 0, 'mhmap' => 0], 'medicines' => []];
    }

    $handle = fopen($csvFile, 'r');
    $header = fgetcsv($handle); // skip header
    $all = [];
    $counts = ['total' => 0, 'regular' => 0, 'donation' => 0, 'mhmap' => 0, 'low_stock' => 0, 'near_expiry' => 0];

    $itemCount = 0;
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[2])) continue;

        $section = trim(strtolower($row[0] ?: 'regular'));
        $itemNo = (int)($row[1] ?: ++$itemCount);
        $name = trim($row[2]);
        $uom = trim($row[3] ?: 'unit');
        $unitPrice = (float)str_replace(['₱', ','], '', $row[4] ?: 0);
        $dpriPrice = (float)str_replace(['₱', ','], '', $row[5] ?: 0);
        $category = trim($row[6] ?: 'General');
        $expiryDate = trim($row[7] ?: '2028-12-31');
        $lotNumber = trim($row[8] ?: 'N/A');
        $begBalance = (int)str_replace(',', '', $row[9] ?: 0);
        $delDate = trim($row[10] ?: '');
        $qtyDel = trim($row[11] ?: '');
        $endBalance = (int)str_replace(',', '', $row[12] ?: 0);
        $consumption = (int)str_replace(',', '', $row[13] ?: 0);
        $rawStatus = strtoupper(trim($row[14] ?: 'IN STOCK'));

        $status = 'IN STOCK';
        if (strpos($rawStatus, 'OUT') !== false || $endBalance === 0) {
            $status = 'OUT OF STOCK';
        } elseif (strpos($rawStatus, 'CRITICAL') !== false || $endBalance <= 20) {
            $status = 'CRITICAL STOCK';
        }

        $counts['total']++;
        if (isset($counts[$section])) {
            $counts[$section]++;
        }
        if ($status !== 'IN STOCK') {
            $counts['low_stock']++;
        }

        // Apply filters
        if ($stockType !== 'all' && $section !== $stockType) {
            continue;
        }

        if (!empty($search)) {
            $haystack = strtolower("$name $uom $category $lotNumber $section");
            if (strpos($haystack, $search) === false) {
                continue;
            }
        }

        $all[] = [
            'id'                  => $itemNo,
            'item_no'             => $itemNo,
            'sku'                 => strtoupper(substr($section, 0, 3)) . '-' . str_pad((string)$itemNo, 3, '0', STR_PAD_LEFT),
            'stock_type'          => $section,
            'name'                => $name,
            'unit_of_measure'     => $uom,
            'dosage_form'         => ucfirst($uom),
            'unit_price'          => $unitPrice,
            'dpri_price'          => $dpriPrice,
            'category_name'       => $category,
            'expiry_date'         => $expiryDate,
            'batch_number'        => $lotNumber,
            'beginning_balance'   => $begBalance,
            'delivery_date'       => $delDate,
            'qty_delivered'       => $qtyDel,
            'quantity'            => $endBalance,
            'ending_balance'      => $endBalance,
            'monthly_consumption' => $consumption,
            'status'              => $status
        ];
    }
    fclose($handle);

    return ['stats' => $counts, 'medicines' => $all];
}

// Try MySQL first, fallback to CSV
if ($pdo) {
    try {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(LOWER(m.name) LIKE :s1 OR LOWER(m.sku) LIKE :s2 OR LOWER(m.category_name) LIKE :s3 OR LOWER(m.batch_number) LIKE :s4)";
            $term = "%{$search}%";
            $params['s1'] = $term;
            $params['s2'] = $term;
            $params['s3'] = $term;
            $params['s4'] = $term;
        }

        if ($stockType !== 'all' && in_array($stockType, ['regular', 'donation', 'mhmap'], true)) {
            $where[] = "m.stock_type = :st";
            $params['st'] = $stockType;
        }

        $sql = "SELECT * FROM medicines m";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY m.id ASC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $medicines = $stmt->fetchAll();

        if (count($medicines) > 0) {
            foreach ($medicines as &$medicine) {
                $reportData = json_decode($medicine['report_data'] ?? '', true);
                if (!is_array($reportData)) {
                    continue;
                }
                foreach ([
                    'dpri_price',
                    'category_name',
                    'other_category',
                    'beginning_balance',
                    'delivery_date',
                    'qty_delivered',
                    'monthly_consumption'
                ] as $field) {
                    if (array_key_exists($field, $reportData)) {
                        $medicine[$field] = $reportData[$field];
                    }
                }
                $medicine['daily_consumption'] = $reportData['daily_consumption'] ?? [];
                $medicine['special_entries'] = $reportData['special_entries'] ?? [];
                $medicine['weekly_consumption'] = $reportData['weekly_consumption'] ?? [];
            }
            unset($medicine);

            $total = (int)$pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
            $regularCount = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE stock_type = 'regular'")->fetchColumn();
            $donationCount = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE stock_type = 'donation'")->fetchColumn();
            $mhmapCount = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE stock_type = 'mhmap'")->fetchColumn();
            $lowStock = (int)$pdo->query("SELECT COUNT(*) FROM medicines WHERE quantity <= min_alert_qty")->fetchColumn();

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total'       => $total,
                    'regular'     => $regularCount,
                    'donation'    => $donationCount,
                    'mhmap'       => $mhmapCount,
                    'low_stock'   => $lowStock
                ],
                'medicines' => $medicines
            ]);
            exit;
        }
    } catch (Exception $e) {
        // Fallback to CSV below
    }
}

// Seamlessly serve from the real Hospital Spreadsheet CSV data
$result = loadFromCSV($stockType, $search);
echo json_encode([
    'success'   => true,
    'stats'     => $result['stats'],
    'medicines' => $result['medicines']
]);
