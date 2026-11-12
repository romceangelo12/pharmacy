<?php
/**
 * JVGH Pharmacy - Main Inventory Dashboard
 */

require_once __DIR__ . '/config.php';

// Protect route
requireLogin();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Fetch live statistics if DB is connected
$totalMedicines = 0;
$lowStockCount = 0;
$nearExpiryCount = 0;
$sampleMedicines = [];

if ($pdo) {
    try {
        // Total active medications
        $stmt = $pdo->query("SELECT COUNT(*) FROM medicines");
        $totalMedicines = (int)$stmt->fetchColumn();

        // Low stock count (quantity <= min_alert_qty)
        $stmt = $pdo->query("SELECT COUNT(*) FROM medicines WHERE quantity <= min_alert_qty");
        $lowStockCount = (int)$stmt->fetchColumn();

        // Expiring within 60 days
        $stmt = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)");
        $nearExpiryCount = (int)$stmt->fetchColumn();

        // Sample list of medicines
        $stmt = $pdo->query("
            SELECT m.*, c.name AS category_name 
            FROM medicines m 
            LEFT JOIN categories c ON m.category_id = c.id 
            ORDER BY m.id ASC LIMIT 10
        ");
        $sampleMedicines = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback gracefully
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard &mdash; JVGH Pharmacy</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- ============================================== -->
    <!-- SIDEBAR NAVIGATION                             -->
    <!-- ============================================== -->
    <aside class="app-sidebar" id="appSidebar">
        <!-- Brand Header -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="logo.jpg" alt="JVGH Pharmacy logo">
            </div>
            <div>
                <div class="sidebar-brand-name">JVGH Pharmacy</div>
                <span class="sidebar-brand-sub">Inventory System</span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <div class="nav-section-title">Main Menu</div>

            <a href="dashboard.php" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Medicines catalogue is available in full inventory view.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
                    <path d="m8.5 8.5 7 7"></path>
                </svg>
                <span>Medicines & Drugs</span>
                <span class="nav-badge nav-badge-neutral"><?= $totalMedicines ?></span>
            </a>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Manage categories: Antibiotics, Analgesics, Vitamins, etc.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                </svg>
                <span>Categories</span>
            </a>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Stock intake module: log received supplier batches and GRNs.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                    <path d="m3.3 7 8.7 5 8.7-5"></path>
                    <path d="M12 22V12"></path>
                </svg>
                <span>Stock Intake (GRN)</span>
            </a>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Prescription Dispense & POS module.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Dispense / POS</span>
            </a>

            <div class="nav-section-title">Stock Monitoring</div>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Showing items that reached reorder minimum threshold.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span>Low Stock Alerts</span>
                <?php if ($lowStockCount > 0): ?>
                    <span class="nav-badge nav-badge-warning"><?= $lowStockCount ?></span>
                <?php endif; ?>
            </a>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Medications nearing expiration within 60 days.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Near Expiry</span>
                <?php if ($nearExpiryCount > 0): ?>
                    <span class="nav-badge nav-badge-danger"><?= $nearExpiryCount ?></span>
                <?php endif; ?>
            </a>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Audit logs: movement history and timestamps.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Audit Logs</span>
            </a>

            <div class="nav-section-title">Administration</div>

            <a href="javascript:void(0)" class="nav-item" onclick="alert('Manage staff accounts and permissions.');">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Staff & Roles</span>
            </a>

            <a href="install.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Database / Setup</span>
            </a>
        </nav>

        <!-- Sidebar User Footer -->
        <div class="sidebar-footer">
            <div class="user-profile-card">
                <div class="user-avatar-circle">
                    <?= strtoupper(substr($currentUser['full_name'] ?: 'U', 0, 1)) ?>
                </div>
                <div class="user-meta">
                    <div class="user-meta-name"><?= htmlspecialchars($currentUser['full_name']) ?></div>
                    <div class="user-meta-role"><?= htmlspecialchars(str_replace('_', ' ', $currentUser['role'])) ?></div>
                </div>
                <a href="logout.php" class="logout-icon-btn" title="Sign out" aria-label="Sign out">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- ============================================== -->
    <!-- MAIN APPLICATION VIEW                          -->
    <!-- ============================================== -->
    <div class="app-main">
        <!-- Top Navigation Bar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="menu-toggle-btn" id="menuToggle" aria-label="Toggle navigation">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <div class="topbar-search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" placeholder="Search medicines, SKU, batch...">
                </div>
            </div>

            <div class="topbar-right">
                <div class="time-badge">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span><?= date('D, M j, Y') ?></span>
                </div>

                <div>
                    <span class="role-badge role-<?= htmlspecialchars($currentUser['role']) ?>">
                        <?= htmlspecialchars(str_replace('_', ' ', $currentUser['role'])) ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Dashboard Content Body -->
        <div class="dashboard-content">
            <div class="page-header">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 700; color: #0f172a;">Inventory Overview</h1>
                    <p style="color: #64748b; font-size: 0.88rem; margin-top: 2px;">Real-time stock alerts, batches, and current pharmaceutical records.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-primary" onclick="alert('Medication intake modal / form.');">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        New Stock Intake
                    </button>
                </div>
            </div>

            <!-- Metric KPI Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p>Total Medications</p>
                        <h3><?= $totalMedicines ?></h3>
                    </div>
                    <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Low Stock Warnings</p>
                        <h3 style="color: <?= $lowStockCount > 0 ? '#d97706' : '#0f172a' ?>;"><?= $lowStockCount ?></h3>
                    </div>
                    <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Near Expiry (60 Days)</p>
                        <h3 style="color: <?= $nearExpiryCount > 0 ? '#dc2626' : '#0f172a' ?>;"><?= $nearExpiryCount ?></h3>
                    </div>
                    <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Database Status</p>
                        <h3 style="font-size: 1.05rem; color: #059669; margin-top: 4px;">Connected</h3>
                    </div>
                    <div class="stat-icon" style="background: #d1fae5; color: #059669;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Inventory Data Table -->
            <div class="table-card">
                <div class="table-header">
                    <div>
                        <h2 style="font-size: 1.1rem; font-weight: 700; color: #0f172a;">Current Pharmaceutical Stock</h2>
                        <p style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Real-time quantities, dosage formulations, batches, and shelf-life</p>
                    </div>
                    <a href="database.sql" download class="btn btn-outline" style="font-size: 0.82rem; padding: 6px 12px;">
                        Download SQL Backup
                    </a>
                </div>

                <div class="data-table-wrapper">
                    <?php if (!empty($sampleMedicines)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Medication Name</th>
                                    <th>Category</th>
                                    <th>Batch No.</th>
                                    <th>Dosage Form</th>
                                    <th>Unit Price</th>
                                    <th>In Stock</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sampleMedicines as $med): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($med['sku']) ?></code></td>
                                        <td>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($med['name']) ?></div>
                                            <div style="font-size: 0.75rem; color: #64748b;"><?= htmlspecialchars($med['generic_name'] ?? '') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($med['category_name'] ?? 'General') ?></td>
                                        <td><span style="font-family: monospace; font-size: 0.82rem;"><?= htmlspecialchars($med['batch_number'] ?? 'N/A') ?></span></td>
                                        <td><?= htmlspecialchars($med['dosage_form']) ?></td>
                                        <td>$<?= number_format((float)$med['unit_price'], 2) ?></td>
                                        <td>
                                            <strong style="color: <?= $med['quantity'] <= $med['min_alert_qty'] ? '#dc2626' : '#0f172a' ?>;">
                                                <?= (int)$med['quantity'] ?>
                                            </strong>
                                        </td>
                                        <td><?= htmlspecialchars($med['expiry_date']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= htmlspecialchars($med['status']) ?>">
                                                <?= ucwords(str_replace('_', ' ', $med['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center; color: #64748b;">
                            <p>No inventory records found.</p>
                            <p style="font-size: 0.85rem; margin-top: 6px;">You can seed demo stock from the <a href="install.php" style="color: #0d9488; text-decoration: underline;">Database Setup</a>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // Responsive Mobile Sidebar Toggle
    const menuToggle = document.getElementById('menuToggle');
    const appSidebar = document.getElementById('appSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function toggleSidebar() {
        appSidebar.classList.toggle('open');
        sidebarBackdrop.classList.toggle('active');
    }

    if (menuToggle && appSidebar) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', toggleSidebar);
    }
</script>

</body>
</html>
