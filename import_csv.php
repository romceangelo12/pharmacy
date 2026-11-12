<?php
/**
 * Import hospital inventory CSV into database
 */

require_once __DIR__ . '/config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("Database not connected\n");
}

// 1. Ensure table columns exist
$cols = [
    "item_no INT DEFAULT 0",
    "unit_of_measure VARCHAR(50) DEFAULT 'tablet'",
    "dpri_price DECIMAL(10,2) DEFAULT 0.00",
    "category_name VARCHAR(100) DEFAULT 'General'",
    "beginning_balance INT DEFAULT 0",
    "monthly_consumption INT DEFAULT 0",
    "delivery_date VARCHAR(100) NULL",
    "qty_delivered VARCHAR(100) NULL",
    "stock_type ENUM('regular', 'donation', 'mhmap') NOT NULL DEFAULT 'regular'"
];

foreach ($cols as $colDef) {
    $colName = explode(" ", $colDef)[0];
    $check = $pdo->query("SHOW COLUMNS FROM `medicines` LIKE '$colName'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE `medicines` ADD COLUMN $colDef");
    }
}

// 2. Read and parse CSV
$csvFile = __DIR__ . '/data/hospital_inventory.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found\n");
}

$handle = fopen($csvFile, 'r');
$header = fgetcsv($handle); // skip header

// Truncate or prepare
$pdo->exec("DELETE FROM medicines");

$stmt = $pdo->prepare("
    INSERT INTO `medicines` (
        `sku`, `item_no`, `name`, `stock_type`, `unit_of_measure`, `dosage_form`,
        `unit_price`, `dpri_price`, `category_name`, `expiry_date`, `batch_number`,
        `beginning_balance`, `quantity`, `monthly_consumption`, `delivery_date`, `qty_delivered`, `status`
    ) VALUES (
        :sku, :item_no, :name, :stock_type, :unit_of_measure, :dosage_form,
        :unit_price, :dpri_price, :category_name, :expiry_date, :batch_number,
        :beginning_balance, :quantity, :monthly_consumption, :delivery_date, :qty_delivered, :status
    )
");

$count = 0;
while (($row = fgetcsv($handle)) !== false) {
    if (empty($row[2])) continue;

    $section     = trim($row[0] ?: 'regular');
    $itemNo      = (int)($row[1] ?: ++$count);
    $name        = trim($row[2]);
    $uom         = trim($row[3] ?: 'unit');
    $unitPrice   = (float)str_replace(['₱', ','], '', $row[4] ?: 0);
    $dpriPrice   = (float)str_replace(['₱', ','], '', $row[5] ?: 0);
    $category    = trim($row[6] ?: 'General');
    $expiryDate  = trim($row[7] ?: '2028-12-31');
    $lotNumber   = trim($row[8] ?: 'N/A');
    $begBalance  = (int)str_replace(',', '', $row[9] ?: 0);
    $delDate     = trim($row[10] ?: '');
    $qtyDel      = trim($row[11] ?: '');
    $endBalance  = (int)str_replace(',', '', $row[12] ?: 0);
    $consumption = (int)str_replace(',', '', $row[13] ?: 0);
    $rawStatus   = strtoupper(trim($row[14] ?: 'IN STOCK'));

    $status = 'in_stock';
    if (strpos($rawStatus, 'OUT') !== false || $endBalance === 0) {
        $status = 'out_of_stock';
    } elseif (strpos($rawStatus, 'CRITICAL') !== false || $endBalance <= 20) {
        $status = 'critical_stock';
    }

    $sku = strtoupper(substr($section, 0, 3)) . '-' . str_pad((string)$itemNo, 3, '0', STR_PAD_LEFT);

    // Format valid date if needed
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
        $expiryDate = '2028-12-31';
    }

    $stmt->execute([
        'sku'                 => $sku,
        'item_no'             => $itemNo,
        'name'                => $name,
        'stock_type'          => $section,
        'unit_of_measure'     => $uom,
        'dosage_form'         => ucfirst($uom),
        'unit_price'          => $unitPrice,
        'dpri_price'          => $dpriPrice,
        'category_name'       => $category,
        'expiry_date'         => $expiryDate,
        'batch_number'        => $lotNumber,
        'beginning_balance'   => $begBalance,
        'quantity'            => $endBalance,
        'monthly_consumption' => $consumption,
        'delivery_date'       => $delDate,
        'qty_delivered'       => $qtyDel,
        'status'              => $status
    ]);
    $count++;
}

fclose($handle);
echo "Successfully imported $count inventory items from hospital spreadsheet!\n";

