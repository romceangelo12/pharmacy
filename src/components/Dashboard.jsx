import React, { useState, useEffect } from 'react';
import Sidebar from './Sidebar';

const createEmptyItem = () => ({
  name: '',
  generic_name: '',
  stock_type: 'regular',
  unit_of_measure: 'tablet',
  unit_price: '0',
  dpri_price: '0',
  category_name: '',
  other_category: '',
  expiry_date: '',
  batch_number: '',
  beginning_balance: '0',
  delivery_date: '',
  qty_delivered: '',
  daily_consumption: Array(30).fill(''),
  special_entries: {
    'sl-return': '',
    'lgu-obando': '',
    inv: '',
    'sl-ret': ''
  },
  quantity: '0',
  monthly_consumption: '0',
  min_alert_qty: '20'
});

const weeklyConsumptionRanges = [
  { label: '1-7', start: 1, end: 7 },
  { label: '8-14', start: 8, end: 14 },
  { label: '15-21', start: 15, end: 21 },
  { label: '22-28', start: 22, end: 28 },
  { label: '29-30', start: 29, end: 30 }
];

const unitOfMeasureOptions = [
  'tablet',
  'capsule',
  'vial',
  'syrup',
  'drops',
  'suspension',
  'cream',
  'bottle',
  'ampule',
  'syringe'
];

const calculateWeeklyConsumption = (dailyConsumption) => weeklyConsumptionRanges.map(({ label, start, end }) => ({
  label,
  total: dailyConsumption.slice(start - 1, end).reduce((sum, value) => sum + (Number(value) || 0), 0)
}));

export default function Dashboard({ user, onLogout }) {
  const [stats, setStats] = useState({ total: 0, regular: 0, donation: 0, mhmap: 0, low_stock: 0, near_expiry: 0 });
  const [medicines, setMedicines] = useState([]);
  const [search, setSearch] = useState('');
  const [unitFilter, setUnitFilter] = useState('ALL');
  const [categoryFilter, setCategoryFilter] = useState('ALL');
  const [otherCategoryFilter, setOtherCategoryFilter] = useState('ALL');
  const [reportMonth, setReportMonth] = useState('2026-11');
  const [loading, setLoading] = useState(true);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard'); // 'dashboard' | 'regular' | 'donation' | 'mhmap'
  const [showAddItem, setShowAddItem] = useState(false);
  const [savingItem, setSavingItem] = useState(false);
  const [addItemError, setAddItemError] = useState('');
  const [newItem, setNewItem] = useState(createEmptyItem);

  const fetchData = async (tab = activeTab, searchTerm = search) => {
    setLoading(true);
    try {
      const stockTypeParam = tab === 'dashboard' ? 'all' : tab;
      const res = await fetch(`api/inventory.php?stock_type=${stockTypeParam}&search=${encodeURIComponent(searchTerm)}`);
      const data = await res.json();
      if (data.success) {
        setStats(data.stats || { total: 0, regular: 0, donation: 0, mhmap: 0, low_stock: 0, near_expiry: 0 });
        setMedicines(data.medicines || []);
      }
    } catch (e) {
      console.error('Failed to load inventory', e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setUnitFilter('ALL');
    setCategoryFilter('ALL');
    setOtherCategoryFilter('ALL');
    if (activeTab === 'regular' && search) {
      setSearch('');
      fetchData(activeTab, '');
    } else {
      fetchData(activeTab, search);
    }
  }, [activeTab]);

  const handleSearchChange = (e) => {
    const val = e.target.value;
    setSearch(val);
    fetchData(activeTab, val);
  };

  const handleNewItemChange = (e) => {
    const { name, value } = e.target;
    setNewItem((current) => ({ ...current, [name]: value }));
  };

  const handleDailyConsumptionChange = (day, value) => {
    setNewItem((current) => ({
      ...current,
      daily_consumption: current.daily_consumption.map((amount, index) => index === day - 1 ? value : amount)
    }));
  };

  const openAddItem = () => {
    setNewItem(createEmptyItem());
    setAddItemError('');
    setShowAddItem(true);
  };

  const handleAddItemSubmit = async (e) => {
    e.preventDefault();
    setAddItemError('');
    setSavingItem(true);

    try {
      const response = await fetch('api/inventory.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newItem)
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Unable to add this inventory item.');
      }

      setShowAddItem(false);
      setSearch('');
      setUnitFilter('ALL');
      setCategoryFilter('ALL');
      setOtherCategoryFilter('ALL');
      setActiveTab('regular');
      await fetchData('regular', '');
    } catch (error) {
      setAddItemError(error instanceof Error ? error.message : 'Unable to add this inventory item.');
    } finally {
      setSavingItem(false);
    }
  };

  const categoryOptions = [...new Set(
    medicines
      .map((med) => (med.category_name || '').trim())
      .filter(Boolean)
  )].sort((a, b) => a.localeCompare(b));

  const otherCategoryOptions = [...new Set(
    medicines
      .map((med) => (med.other_category || '').trim())
      .filter(Boolean)
  )].sort((a, b) => a.localeCompare(b));

  const filteredMedicines = medicines.filter(med => {
    const unitMatches = unitFilter === 'ALL'
      || (med.unit_of_measure || med.dosage_form || '').trim().toLowerCase() === unitFilter;
    const categoryMatches = categoryFilter === 'ALL'
      || (med.category_name || '').trim().toLowerCase() === categoryFilter.toLowerCase();
    const otherCategoryMatches = otherCategoryFilter === 'ALL'
      || (med.other_category || '').trim().toLowerCase() === otherCategoryFilter.toLowerCase();
    return unitMatches && categoryMatches && otherCategoryMatches;
  });

  // Calculate totals from table data
  const totalBeginning = filteredMedicines.reduce((sum, m) => sum + (parseInt(m.beginning_balance) || 0), 0);
  const totalEnding = filteredMedicines.reduce((sum, m) => sum + (parseInt(m.ending_balance ?? m.quantity) || 0), 0);
  const totalConsumption = filteredMedicines.reduce((sum, m) => sum + (parseInt(m.monthly_consumption) || 0), 0);
  const totalValue = filteredMedicines.reduce((sum, m) => sum + ((parseFloat(m.unit_price) || 0) * (parseInt(m.ending_balance ?? m.quantity) || 0)), 0);

  // Tab Header Details
  const reportMonthLabel = reportMonth
    ? new Date(`${reportMonth}-01T12:00:00`).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
    : '';
  const getTabTitle = () => {
    switch (activeTab) {
      case 'regular':
        return {
          title: 'REPORT ON PHYSICAL COUNT OF INVENTORIES (REGULAR STOCK)',
          subtitle: `Appendix 66 - As of ${reportMonthLabel} • JVGH Pharmacy`
        };
      case 'donation':
        return {
          title: 'REPORT ON PHYSICAL COUNT OF INVENTORIES (DONATION)',
          subtitle: 'Appendix 66 - As of November 2026 • JVGH Pharmacy'
        };
      case 'mhmap':
        return {
          title: 'MENTAL HEALTH MEDICINES ACCESS PROGRAM (MHMAP)',
          subtitle: 'Specialized Psychotropic & Neurologic Medications Inventory'
        };
      case 'dashboard':
      default:
        return {
          title: 'MASTER HOSPITAL INVENTORY SPREADSHEET',
          subtitle: 'Consolidated Inventory & Consumption Report (Regular, Donation & MHMAP)'
        };
    }
  };

  const tabInfo = getTabTitle();
  const topbarTitle = {
    dashboard: 'Dashboard',
    regular: 'Regular Stock',
    donation: 'Donation',
    mhmap: 'MHMAP'
  }[activeTab];

  return (
    <div className="app-wrapper">
      <Sidebar
        user={user}
        onLogout={onLogout}
        activeTab={activeTab}
        setActiveTab={setActiveTab}
        stats={stats}
        isOpen={sidebarOpen}
        isCollapsed={sidebarCollapsed}
        onToggleCollapse={() => setSidebarCollapsed(!sidebarCollapsed)}
        onClose={() => setSidebarOpen(false)}
      />

      <div
        className={`sidebar-backdrop ${sidebarOpen ? 'active' : ''}`}
        onClick={() => setSidebarOpen(false)}
      ></div>

      <div className="app-main">
        {/* Topbar */}
        <header className="app-topbar">
          <div className="topbar-left">
            <button
              type="button"
              className="menu-toggle-btn"
              onClick={() => setSidebarOpen(!sidebarOpen)}
              aria-label="Toggle navigation"
            >
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
              </svg>
            </button>
            <div style={{ fontSize: '0.95rem', fontWeight: 700, color: '#334155' }}>{topbarTitle}</div>
          </div>
          {activeTab === 'regular' && (
            <div className="topbar-right">
              <button type="button" onClick={openAddItem} className="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add New Items
              </button>
            </div>
          )}
        </header>

        {/* Main Content */}
        <div className="dashboard-content" style={{ padding: activeTab === 'regular' ? '20px 24px 84px' : '20px 24px' }}>
          {activeTab === 'dashboard' ? (
            <DashboardOverview stats={stats} loading={loading} onSelectTab={setActiveTab} />
          ) : (
          <>
          {/* Filter Bar & Export Actions */}
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14, flexWrap: 'wrap', gap: 10 }}>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
              <input
                id="inventorySearch"
                type="search"
                value={search}
                onChange={handleSearchChange}
                placeholder="Search items..."
                style={{
                  width: 220,
                  maxWidth: '100%',
                  padding: '7px 10px',
                  border: '1px solid #cbd5e1',
                  borderRadius: 8,
                  background: '#ffffff',
                  color: '#334155',
                  fontSize: '0.82rem'
                }}
              />
              <select
                id="unitOfMeasureFilter"
                value={unitFilter}
                onChange={(e) => setUnitFilter(e.target.value)}
                style={{
                  minWidth: 150,
                  padding: '7px 10px',
                  border: '1px solid #cbd5e1',
                  borderRadius: 8,
                  background: '#ffffff',
                  color: '#334155',
                  fontSize: '0.82rem'
                }}
              >
                <option value="ALL">All units</option>
                {unitOfMeasureOptions.map((unit) => (
                  <option key={unit} value={unit}>{unit}</option>
                ))}
              </select>
              <select
                id="categoryFilter"
                value={categoryFilter}
                onChange={(e) => setCategoryFilter(e.target.value)}
                style={{
                  minWidth: 180,
                  padding: '7px 10px',
                  border: '1px solid #cbd5e1',
                  borderRadius: 8,
                  background: '#ffffff',
                  color: '#334155',
                  fontSize: '0.82rem'
                }}
              >
                <option value="ALL">All categories</option>
                {categoryOptions.map((category) => (
                  <option key={category} value={category}>{category}</option>
                ))}
              </select>
              <select
                id="otherCategoryFilter"
                value={otherCategoryFilter}
                onChange={(e) => setOtherCategoryFilter(e.target.value)}
                style={{
                  minWidth: 180,
                  padding: '7px 10px',
                  border: '1px solid #cbd5e1',
                  borderRadius: 8,
                  background: '#ffffff',
                  color: '#334155',
                  fontSize: '0.82rem'
                }}
              >
                <option value="ALL">All other categories</option>
                {otherCategoryOptions.map((category) => (
                  <option key={category} value={category}>{category}</option>
                ))}
              </select>
              {activeTab === 'regular' && (
                <>
                  <input
                    type="month"
                    aria-label="Report month"
                    title="Change report month"
                    value={reportMonth}
                    onChange={(e) => setReportMonth(e.target.value)}
                    style={{
                      minWidth: 150,
                      padding: '7px 10px',
                      border: '1px solid #cbd5e1',
                      borderRadius: 8,
                      background: '#ffffff',
                      color: '#334155',
                      fontSize: '0.82rem'
                    }}
                  />
                </>
              )}
            </div>

          </div>

          {/* Spreadsheet Table (Matches Appendix 66 / Hospital Excel structure) */}
          {activeTab === 'regular' ? (
            <RegularInventoryTable medicines={filteredMedicines} loading={loading} search={search} monthLabel={reportMonthLabel} />
          ) : (
          <div style={{ background: '#ffffff', borderRadius: 12, border: '1px solid #cbd5e1', boxShadow: '0 2px 8px rgba(0,0,0,0.06)', overflow: 'hidden' }}>
            <div style={{ overflowX: 'auto', maxHeight: '72vh' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '0.82rem', textAlign: 'left', minWidth: 1200 }}>
                <thead style={{ position: 'sticky', top: 0, zIndex: 10, background: '#f8fafc', borderBottom: '2px solid #cbd5e1' }}>
                  <tr style={{ color: '#334155', fontWeight: 700 }}>
                    <th style={{ padding: '12px 14px', borderRight: '1px solid #e2e8f0', width: 45, textAlign: 'center' }}>#</th>
                    <th style={{ padding: '12px 16px', borderRight: '1px solid #e2e8f0', minWidth: 260 }}>ITEMS / DESCRIPTION</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 110 }}>UNIT OF MEASURE</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 100, textAlign: 'right' }}>UNIT PRICE</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 100, textAlign: 'right' }}>DPRI PRICE</th>
                    <th style={{ padding: '12px 14px', borderRight: '1px solid #e2e8f0', minWidth: 160 }}>CATEGORY</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 110 }}>EXPIRY DATE</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 120 }}>LOT NUMBER</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 110, textAlign: 'right' }}>BEG. BAL</th>
                    <th style={{ padding: '12px 14px', borderRight: '1px solid #e2e8f0', minWidth: 140 }}>DELIVERY INFO</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 110, textAlign: 'right', background: '#f0fdf4' }}>ENDING BAL</th>
                    <th style={{ padding: '12px 12px', borderRight: '1px solid #e2e8f0', width: 110, textAlign: 'right' }}>CONSUMPTION</th>
                    <th style={{ padding: '12px 14px', width: 130, textAlign: 'center' }}>STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr>
                      <td colSpan="13" style={{ padding: 50, textAlign: 'center', color: '#64748b' }}>
                        Loading {activeTab} spreadsheet data...
                      </td>
                    </tr>
                  ) : filteredMedicines.length > 0 ? (
                    filteredMedicines.map((item, idx) => {
                      const endBal = parseInt(item.ending_balance ?? item.quantity) || 0;
                      const begBal = parseInt(item.beginning_balance) || 0;
                      const uPrice = parseFloat(item.unit_price) || 0;
                      const dpriPrice = parseFloat(item.dpri_price) || 0;
                      const isCritical = item.status?.includes('CRITICAL') || item.status?.includes('LOW') || (endBal > 0 && endBal <= 20);
                      const isOutOfStock = item.status?.includes('OUT') || endBal === 0;

                      return (
                        <tr 
                          key={item.id || idx}
                          style={{ 
                            borderBottom: '1px solid #e2e8f0',
                            backgroundColor: idx % 2 === 0 ? '#ffffff' : '#f8fafc',
                            transition: 'background-color 0.1s ease'
                          }}
                        >
                          {/* 1. Item No. */}
                          <td style={{ padding: '10px 14px', borderRight: '1px solid #e2e8f0', textAlign: 'center', color: '#64748b', fontWeight: 600 }}>
                            {item.item_no || idx + 1}
                          </td>

                          {/* 2. Items / Description */}
                          <td style={{ padding: '10px 16px', borderRight: '1px solid #e2e8f0' }}>
                            <div style={{ fontWeight: 600, color: '#0f172a' }}>{item.name}</div>
                            {item.generic_name && item.generic_name !== item.name && (
                              <div style={{ fontSize: '0.72rem', color: '#64748b' }}>{item.generic_name}</div>
                            )}
                          </td>

                          {/* 3. Unit of Measure */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', textTransform: 'lowercase', color: '#475569' }}>
                            {item.unit_of_measure || item.dosage_form || 'piece'}
                          </td>

                          {/* 4. Unit Price */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', textAlign: 'right', fontWeight: 600, color: '#334155' }}>
                            {uPrice > 0 ? `₱${uPrice.toFixed(2)}` : '—'}
                          </td>

                          {/* 5. DPRI Price */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', textAlign: 'right', color: '#64748b' }}>
                            {dpriPrice > 0 ? `₱${dpriPrice.toFixed(2)}` : '—'}
                          </td>

                          {/* 6. Category */}
                          <td style={{ padding: '10px 14px', borderRight: '1px solid #e2e8f0', color: '#475569' }}>
                            <span style={{ fontSize: '0.72rem', background: '#f1f5f9', padding: '2px 6px', borderRadius: 4, fontWeight: 500 }}>
                              {item.category_name || 'General'}
                            </span>
                          </td>

                          {/* 7. Expiry Date */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', color: '#475569', fontSize: '0.78rem' }}>
                            {item.expiry_date}
                          </td>

                          {/* 8. Lot Number */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', fontFamily: 'monospace', fontSize: '0.75rem', color: '#334155' }}>
                            {item.batch_number || 'N/A'}
                          </td>

                          {/* 9. Beginning Balance */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', textAlign: 'right', color: '#64748b' }}>
                            {begBal.toLocaleString()}
                          </td>

                          {/* 10. Delivery Info */}
                          <td style={{ padding: '10px 14px', borderRight: '1px solid #e2e8f0', fontSize: '0.74rem', color: '#64748b' }}>
                            {item.qty_delivered || item.delivery_date ? (
                              <div>
                                <div>{item.qty_delivered}</div>
                                {item.delivery_date && <div style={{ fontSize: '0.68rem', color: '#94a3b8' }}>{item.delivery_date}</div>}
                              </div>
                            ) : (
                              '—'
                            )}
                          </td>

                          {/* 11. Ending Balance (On Hand) */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', textAlign: 'right', background: isOutOfStock ? '#fee2e2' : isCritical ? '#fef3c7' : '#f0fdf4' }}>
                            <strong style={{ 
                              fontSize: '0.92rem',
                              color: isOutOfStock ? '#991b1b' : isCritical ? '#92400e' : '#065f46' 
                            }}>
                              {endBal.toLocaleString()}
                            </strong>
                          </td>

                          {/* 12. Monthly Consumption */}
                          <td style={{ padding: '10px 12px', borderRight: '1px solid #e2e8f0', textAlign: 'right', color: '#334155', fontWeight: 500 }}>
                            {(parseInt(item.monthly_consumption) || 0).toLocaleString()}
                          </td>

                          {/* 13. Status Badge */}
                          <td style={{ padding: '10px 14px', textAlign: 'center' }}>
                            <span style={{
                              display: 'inline-block',
                              padding: '3px 8px',
                              borderRadius: '9999px',
                              fontSize: '0.68rem',
                              fontWeight: 700,
                              letterSpacing: '0.04em',
                              background: isOutOfStock ? '#fee2e2' : isCritical ? '#fef3c7' : '#d1fae5',
                              color: isOutOfStock ? '#991b1b' : isCritical ? '#92400e' : '#065f46',
                              border: isOutOfStock ? '1px solid #fca5a5' : isCritical ? '1px solid #fcd34d' : '1px solid #86efac'
                            }}>
                              {isOutOfStock ? 'OUT OF STOCK' : isCritical ? 'CRITICAL STOCK' : 'IN STOCK'}
                            </span>
                          </td>
                        </tr>
                      );
                    })
                  ) : (
                    <tr>
                      <td colSpan="13" style={{ padding: 40, textAlign: 'center', color: '#64748b' }}>
                        No records found matching "{search}".
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>

            {/* Table Footer Summary */}
            <div style={{ padding: '12px 20px', background: '#f8fafc', borderTop: '1px solid #cbd5e1', display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: '0.78rem', color: '#64748b' }}>
              <div>
                Showing <strong>{filteredMedicines.length}</strong> items in <strong>{activeTab.toUpperCase()}</strong> program
              </div>
              <div>
                Report Prepared By: <strong>Juliennevi J. Bautista, RPh</strong> &bull; Noted: <strong>Jocelle C. Custodio, RPh</strong>
              </div>
            </div>
          </div>
          )}
          </>
          )}
          {showAddItem && (
            <div style={{
              position: 'fixed',
              inset: 0,
              zIndex: 200,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              padding: 16,
              background: 'rgba(15, 23, 42, 0.55)'
            }}>
              <section
                role="dialog"
                aria-modal="true"
                aria-labelledby="addItemTitle"
                style={{
                  width: '100%',
                  maxWidth: 620,
                  maxHeight: '90vh',
                  overflowY: 'auto',
                  padding: 24,
                  borderRadius: 14,
                  background: '#ffffff',
                  boxShadow: '0 20px 50px rgba(15, 23, 42, 0.25)'
                }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 }}>
                  <h2 id="addItemTitle" style={{ fontSize: '1.2rem', color: '#0f172a' }}>Add New Inventory Item</h2>
                  <button
                    type="button"
                    onClick={() => setShowAddItem(false)}
                    aria-label="Close dialog"
                    style={{ border: 0, background: 'none', color: '#64748b', fontSize: '1.5rem', cursor: 'pointer' }}
                  >
                    &times;
                  </button>
                </div>

                {addItemError && (
                  <div className="alert alert-danger" role="alert">
                    {addItemError}
                  </div>
                )}

                <form onSubmit={handleAddItemSubmit}>
                  <div className="form-group">
                    <label className="form-label" htmlFor="newItemName">Item Name</label>
                    <input
                      id="newItemName"
                      className="form-control"
                      name="name"
                      value={newItem.name}
                      onChange={handleNewItemChange}
                      maxLength={150}
                      required
                    />
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(210px, 1fr))', gap: '0 12px' }}>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemUnit">Unit of Measure</label>
                      <select
                        id="newItemUnit"
                        className="form-control"
                        name="unit_of_measure"
                        value={newItem.unit_of_measure}
                        onChange={handleNewItemChange}
                        required
                      >
                        {unitOfMeasureOptions.map((unit) => (
                          <option key={unit} value={unit}>{unit}</option>
                        ))}
                      </select>
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemPrice">Unit Price</label>
                      <input
                        id="newItemPrice"
                        className="form-control"
                        type="number"
                        name="unit_price"
                        min="0"
                        step="0.01"
                        value={newItem.unit_price}
                        onChange={handleNewItemChange}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemDpri">DPRI Price</label>
                      <input
                        id="newItemDpri"
                        className="form-control"
                        type="number"
                        name="dpri_price"
                        min="0"
                        step="0.01"
                        value={newItem.dpri_price}
                        onChange={handleNewItemChange}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemCategory">Category</label>
                      <select
                        id="newItemCategory"
                        className="form-control"
                        name="category_name"
                        value={newItem.category_name}
                        onChange={handleNewItemChange}
                        required
                      >
                        <option value="">Select category</option>
                        <option value="SMALL VIALS">SMALL VIALS</option>
                        <option value="ANTI-HYPERTENSIVES">ANTI-HYPERTENSIVES</option>
                        <option value="ANTI-THROMBOTICS">ANTI-THROMBOTICS</option>
                        <option value="ANTI-ASTHMA & COPD">ANTI-ASTHMA &amp; COPD</option>
                        <option value="ANTI-DIABETICS">ANTI-DIABETICS</option>
                        <option value="ANTI-DYSLIPIDEMIA">ANTI-DYSLIPIDEMIA</option>
                        <option value="ANTI-BIOTICS / MICROBIALS">ANTI-BIOTICS / MICROBIALS</option>
                        <option value="NERVOUS SYSTEM">NERVOUS SYSTEM</option>
                        <option value="SUPPORTIVE / OTHER THERAPHY">SUPPORTIVE / OTHER THERAPHY</option>
                        <option value="VITAMINS">VITAMINS</option>
                        <option value="ANTI-ALLERGY/HISTAMINE">ANTI-ALLERGY/HISTAMINE</option>
                        <option value="EXPANDERS">EXPANDERS</option>
                        <option value="DANGEROUS/REGULATED">DANGEROUS/REGULATED</option>
                        <option value="ANALGESICS">ANALGESICS</option>
                        <option value="ANTIDEPRESSANTS / PSYCHOTICS">ANTIDEPRESSANTS / PSYCHOTICS</option>
                        <option value="ANTI-GOUT">ANTI-GOUT</option>
                        <option value="ANTI-VERTIGO">ANTI-VERTIGO</option>
                        <option value="CORTICOSTEROIDS">CORTICOSTEROIDS</option>
                        <option value="DIURETICS">DIURETICS</option>
                        <option value="ANESTHESIA / OR">ANESTHESIA / OR</option>
                        <option value="IV FLUIDS">IV FLUIDS</option>
                        <option value="GASTROINTESTINAL">GASTROINTESTINAL</option>
                        <option value="ELECTROLYTES">ELECTROLYTES</option>
                        <option value="OPTHALMIC PREPARATIONS">OPTHALMIC PREPARATIONS</option>
                        <option value="VACCINES / BIOLOGICALS">VACCINES / BIOLOGICALS</option>
                        <option value="ANTI-THYROID AGENTS">ANTI-THYROID AGENTS</option>
                        <option value="MUSCLE RELAXANT">MUSCLE RELAXANT</option>
                        <option value="SURFACTANT">SURFACTANT</option>
                        <option value="COUGH SUPPRESANTS">COUGH SUPPRESANTS</option>
                        <option value="ANTI-NEOPLASTICS / ONCO">ANTI-NEOPLASTICS / ONCO</option>
                        <option value="CARDIOVASCULAR">CARDIOVASCULAR</option>
                        <option value="BRONCHODILATORS">BRONCHODILATORS</option>
                        <option value="NEUROMUSCULAR BLOCKER">NEUROMUSCULAR BLOCKER</option>
                        <option value="ANTI-CHOLINERGIC">ANTI-CHOLINERGIC</option>
                        <option value="IMMUNOSUPPRESSANTS">IMMUNOSUPPRESSANTS</option>
                        <option value="LAXATIVE">LAXATIVE</option>
                        <option value="ANTIDOTE">ANTIDOTE</option>
                        <option value="ANTI-EPILEPTIC">ANTI-EPILEPTIC</option>
                        <option value="Total Parenteral Nutrition">Total Parenteral Nutrition</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemOtherCategory">Other Category (MOBCHA, YAKAP)</label>
                      <select
                        id="newItemOtherCategory"
                        className="form-control"
                        name="other_category"
                        value={newItem.other_category}
                        onChange={handleNewItemChange}
                      >
                        <option value="">None</option>
                        <option value="MOBCHA">MOBCHA</option>
                        <option value="YAKAP">YAKAP</option>
                        <option value="HIGH ALERT">HIGH ALERT</option>
                        <option value="OR">OR</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemExpiry">Expiry Date</label>
                      <input id="newItemExpiry" className="form-control" type="date" name="expiry_date" value={newItem.expiry_date} onChange={handleNewItemChange} required />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemBatch">Lot Number</label>
                      <input id="newItemBatch" className="form-control" name="batch_number" value={newItem.batch_number} onChange={handleNewItemChange} maxLength={50} />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemBeginning">Beginning Balance</label>
                      <input id="newItemBeginning" className="form-control" type="number" name="beginning_balance" min="0" step="1" value={newItem.beginning_balance} onChange={handleNewItemChange} required />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemDeliveryDate">Delivery Date</label>
                      <input id="newItemDeliveryDate" className="form-control" type="text" name="delivery_date" value={newItem.delivery_date} onChange={handleNewItemChange} maxLength={100} />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemDelivered">Qty Delivered</label>
                      <input id="newItemDelivered" className="form-control" name="qty_delivered" value={newItem.qty_delivered} onChange={handleNewItemChange} maxLength={100} />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemQuantity">Ending Balance</label>
                      <input id="newItemQuantity" className="form-control" type="number" name="quantity" min="0" step="1" value={newItem.quantity} onChange={handleNewItemChange} required />
                    </div>
                    <div className="form-group">
                      <label className="form-label" htmlFor="newItemMonthly">Monthly Consumption</label>
                      <input id="newItemMonthly" className="form-control" type="number" name="monthly_consumption" min="0" step="1" value={newItem.monthly_consumption} onChange={handleNewItemChange} required />
                    </div>
                  </div>

                  <section style={{ borderTop: '1px solid #e2e8f0', paddingTop: 16, marginTop: 8 }}>
                    <h3 style={{ fontSize: '0.95rem', color: '#0f172a', marginBottom: 4 }}>November 2026 Daily Consumption</h3>
                    <p style={{ color: '#64748b', fontSize: '0.78rem', marginBottom: 12 }}>Enter daily consumption; weekly totals are calculated automatically.</p>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(62px, 1fr))', gap: 8 }}>
                      {newItem.daily_consumption.map((amount, index) => (
                        <label key={index} style={{ fontSize: '0.72rem', color: '#64748b' }}>
                          {index + 1}
                          <input
                            type="number"
                            min="0"
                            step="1"
                            value={amount}
                            onChange={(e) => handleDailyConsumptionChange(index + 1, e.target.value)}
                            aria-label={`November ${index + 1} consumption`}
                            style={{ width: '100%', padding: '7px 5px', marginTop: 4, border: '1px solid #cbd5e1', borderRadius: 6 }}
                          />
                        </label>
                      ))}
                    </div>

                    <h4 style={{ fontSize: '0.82rem', color: '#334155', margin: '16px 0 8px' }}>Weekly Consumption</h4>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(90px, 1fr))', gap: 8 }}>
                      {calculateWeeklyConsumption(newItem.daily_consumption).map(({ label, total }) => (
                        <div key={label} style={{ padding: '8px 10px', borderRadius: 8, background: '#f8fafc', color: '#475569', fontSize: '0.78rem' }}>
                          <strong>{label}</strong>: {total.toLocaleString()}
                        </div>
                      ))}
                    </div>
                  </section>

                  <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 8 }}>
                    <button type="button" className="btn btn-outline" onClick={() => setShowAddItem(false)} disabled={savingItem}>
                      Cancel
                    </button>
                    <button type="submit" className="btn btn-primary" disabled={savingItem}>
                      {savingItem ? 'Saving...' : 'Add Item'}
                    </button>
                  </div>
                </form>
              </section>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function DashboardOverview({ stats, loading, onSelectTab }) {
  const total = Number(stats.total) || 0;
  const programs = [
    { key: 'regular', label: 'Regular Stock', count: Number(stats.regular) || 0, color: '#0d9488' },
    { key: 'donation', label: 'Donation', count: Number(stats.donation) || 0, color: '#6366f1' },
    { key: 'mhmap', label: 'MHMAP', count: Number(stats.mhmap) || 0, color: '#f59e0b' }
  ];
  const attentionCount = Number(stats.low_stock) || 0;
  const cardStyle = {
    background: '#ffffff',
    border: '1px solid #e2e8f0',
    borderRadius: 14,
    padding: '20px',
    boxShadow: '0 2px 8px rgba(15, 23, 42, 0.04)'
  };
  const value = (count) => loading ? '...' : count.toLocaleString();

  return (
    <div>
      <div style={{ marginBottom: 22 }}>
        <h1 style={{ fontSize: '1.4rem', color: '#0f172a', marginBottom: 4 }}>Inventory Overview</h1>
        <p style={{ color: '#64748b', fontSize: '0.9rem' }}>A summary of medicine stock across all programs.</p>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(190px, 1fr))', gap: 14, marginBottom: 18 }}>
        {[
          { label: 'Total Medicines', count: total, color: '#0f172a', note: 'Across all programs' },
          { label: 'Regular Stock', count: Number(stats.regular) || 0, color: '#0d9488', note: 'Regular inventory' },
          { label: 'Donations', count: Number(stats.donation) || 0, color: '#6366f1', note: 'Donation inventory' },
          { label: 'MHMAP', count: Number(stats.mhmap) || 0, color: '#d97706', note: 'MHMAP inventory' },
          { label: 'Stock Alerts', count: attentionCount, color: '#dc2626', note: 'Low or critical stock' }
        ].map((card) => (
          <div key={card.label} style={cardStyle}>
            <div style={{ fontSize: '0.78rem', fontWeight: 600, color: '#64748b', marginBottom: 10 }}>{card.label}</div>
            <div style={{ fontSize: '1.8rem', lineHeight: 1.1, fontWeight: 700, color: card.color }}>
              {value(card.count)}
            </div>
            <div style={{ fontSize: '0.75rem', color: '#94a3b8', marginTop: 8 }}>{card.note}</div>
          </div>
        ))}
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 14 }}>
        <section style={cardStyle}>
          <h2 style={{ fontSize: '1rem', color: '#0f172a', marginBottom: 18 }}>Inventory by Program</h2>
          <div style={{ display: 'grid', gap: 18 }}>
            {programs.map((program) => {
              const percentage = total > 0 ? Math.min((program.count / total) * 100, 100) : 0;
              return (
                <button
                  key={program.key}
                  type="button"
                  onClick={() => onSelectTab(program.key)}
                  style={{ textAlign: 'left', border: 0, padding: 0, background: 'none', cursor: 'pointer' }}
                  aria-label={`View ${program.label}`}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, marginBottom: 7, fontSize: '0.82rem' }}>
                    <span style={{ color: '#334155', fontWeight: 600 }}>{program.label}</span>
                    <span style={{ color: '#64748b' }}>{value(program.count)}{!loading && total > 0 ? ` (${Math.round(percentage)}%)` : ''}</span>
                  </div>
                  <div style={{ height: 8, borderRadius: 999, overflow: 'hidden', background: '#f1f5f9' }}>
                    <div style={{ width: `${percentage}%`, height: '100%', background: program.color, borderRadius: 999, transition: 'width 0.2s ease' }} />
                  </div>
                </button>
              );
            })}
          </div>
        </section>

        <section style={{ ...cardStyle, background: '#fff7ed', borderColor: '#fed7aa' }}>
          <h2 style={{ fontSize: '1rem', color: '#7c2d12', marginBottom: 10 }}>Stock Alerts</h2>
          <div style={{ fontSize: '2rem', lineHeight: 1.2, fontWeight: 700, color: '#c2410c' }}>{value(attentionCount)}</div>
          <p style={{ margin: '8px 0 18px', color: '#9a3412', fontSize: '0.84rem', lineHeight: 1.5 }}>
            {attentionCount === 1 ? 'item needs' : 'items need'} attention due to low or critical stock.
          </p>
          <button type="button" className="btn btn-primary" onClick={() => onSelectTab('regular')}>
            Review Regular Stock
          </button>
        </section>
      </div>
    </div>
  );
}

function RegularInventoryTable({ medicines, loading, search, monthLabel }) {
  const dayColumns = [];
  for (let day = 1; day <= 30; day += 1) {
    dayColumns.push({ key: `day-${day}`, label: String(day) });
  }

  const fixedColumns = [
    { key: 'item-no', label: 'NO.', style: { minWidth: 48 } },
    { key: 'name', label: 'ITEMS', style: { minWidth: 220, textAlign: 'left' } },
    { key: 'unit', label: 'UNIT OF MEASURE', style: { minWidth: 110 } },
    { key: 'unit-price', label: 'UNIT PRICE', style: { minWidth: 90 } },
    { key: 'dpri-price', label: 'DPRI PRICE', style: { minWidth: 90 } },
    { key: 'category', label: 'CATEGORY', style: { minWidth: 145, textAlign: 'left' } },
    { key: 'other-category', label: 'OTHER CATEGORY (MOBCHA, YAKAP)', style: { minWidth: 170, textAlign: 'left' } },
    { key: 'expiry', label: 'EXPIRY DATE', style: { minWidth: 105 } },
    { key: 'lot', label: 'LOT NUMBER', style: { minWidth: 105 } },
    { key: 'beginning', label: 'BEGINNING BALANCE', style: { minWidth: 115 } },
    { key: 'delivery-date', label: 'DELIVERY DATE', style: { minWidth: 110 } },
    { key: 'qty-delivered', label: 'QTY DELIVERED', style: { minWidth: 105 } }
  ];
  const weeklyColumns = ['1-7', '8-14', '15-21', '22-28', '29-30'];
  const headerStyle = {
    padding: '9px 8px',
    border: '1px solid #cbd5e1',
    textAlign: 'center',
    whiteSpace: 'normal',
    lineHeight: 1.2,
    fontSize: '0.7rem',
    background: '#f1f5f9'
  };
  const cellStyle = {
    padding: '8px',
    border: '1px solid #e2e8f0',
    textAlign: 'center',
    whiteSpace: 'nowrap',
    color: '#475569'
  };
  return (
    <div style={{ background: '#ffffff', borderRadius: 12, border: '1px solid #cbd5e1', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
      <div style={{ overflow: 'auto', maxHeight: '72vh', borderRadius: '12px 12px 0 0' }}>
        <table style={{ borderCollapse: 'collapse', fontSize: '0.78rem', minWidth: 'max-content' }}>
          <thead style={{ position: 'sticky', top: 0, zIndex: 10 }}>
            <tr>
              {fixedColumns.map(column => (
                <th key={column.key} rowSpan={2} style={{ ...headerStyle, ...column.style }}>
                  {column.label}
                </th>
              ))}
              <th colSpan={dayColumns.length} style={{ ...headerStyle, background: '#dbeafe', fontSize: '0.78rem' }}>
                {monthLabel.toUpperCase()}
              </th>
              <th rowSpan={2} style={{ ...headerStyle, minWidth: 110, background: '#dcfce7' }}>ENDING BALANCE</th>
              <th rowSpan={2} style={{ ...headerStyle, minWidth: 120 }}>MONTHLY CONSUMPTION</th>
              <th colSpan={weeklyColumns.length} style={{ ...headerStyle, background: '#fef3c7', fontSize: '0.78rem' }}>
                WEEKLY CONSUMPTION
              </th>
            </tr>
            <tr>
              {dayColumns.map(column => (
                <th key={column.key} style={{ ...headerStyle, minWidth: column.label.length > 3 ? 92 : 36 }}>
                  {column.label}
                </th>
              ))}
              {weeklyColumns.map(week => (
                <th key={week} style={{ ...headerStyle, minWidth: 70 }}>{week}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={fixedColumns.length + dayColumns.length + 2 + weeklyColumns.length} style={{ ...cellStyle, padding: 40 }}>Loading regular inventory...</td></tr>
            ) : medicines.length ? medicines.map((item, index) => (
              <tr key={item.id || index} style={{ background: index % 2 ? '#f8fafc' : '#ffffff' }}>
                <td style={cellStyle}>{item.item_no || index + 1}</td>
                <td style={{ ...cellStyle, minWidth: 220, textAlign: 'left', whiteSpace: 'normal', color: '#0f172a', fontWeight: 600 }}>{item.name}</td>
                <td style={cellStyle}>{item.unit_of_measure || item.dosage_form || '—'}</td>
                <td style={cellStyle}>{Number(item.unit_price) > 0 ? `₱${Number(item.unit_price).toFixed(2)}` : '—'}</td>
                <td style={cellStyle}>{Number(item.dpri_price) > 0 ? `₱${Number(item.dpri_price).toFixed(2)}` : '—'}</td>
                <td style={{ ...cellStyle, textAlign: 'left' }}>{item.category_name || '—'}</td>
                <td style={{ ...cellStyle, textAlign: 'left' }}>{item.other_category || '—'}</td>
                <td style={cellStyle}>{item.expiry_date || '—'}</td>
                <td style={cellStyle}>{item.batch_number || '—'}</td>
                <td style={cellStyle}>{Number(item.beginning_balance || 0).toLocaleString()}</td>
                <td style={cellStyle}>{item.delivery_date || '—'}</td>
                <td style={cellStyle}>{item.qty_delivered || '—'}</td>
                {dayColumns.map(column => {
                  const value = item.daily_consumption?.[Number(column.key.slice(4)) - 1];
                  return (
                    <td key={column.key} style={cellStyle}>
                      {value === null || value === undefined || value === '' ? '—' : Number(value).toLocaleString()}
                    </td>
                  );
                })}
                <td style={{ ...cellStyle, background: '#f0fdf4', fontWeight: 700 }}>{Number(item.ending_balance ?? item.quantity ?? 0).toLocaleString()}</td>
                <td style={{ ...cellStyle, fontWeight: 600 }}>{Number(item.monthly_consumption || 0).toLocaleString()}</td>
                {weeklyColumns.map(week => (
                  <td key={week} style={cellStyle}>
                    {item.weekly_consumption?.[week] === undefined ? '—' : Number(item.weekly_consumption[week]).toLocaleString()}
                  </td>
                ))}
              </tr>
            )) : (
              <tr><td colSpan={fixedColumns.length + dayColumns.length + 2 + weeklyColumns.length} style={{ ...cellStyle, padding: 40 }}>
                No records found matching "{search}".
              </td></tr>
            )}
          </tbody>
        </table>
      </div>
      <div className="regular-inventory-footer" style={{ padding: '12px 20px', background: '#f8fafc', borderTop: '1px solid #cbd5e1', boxShadow: '0 -4px 10px rgba(15, 23, 42, 0.06)', display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap', fontSize: '0.78rem', color: '#64748b' }}>
        <span>Showing <strong>{medicines.length}</strong> items in <strong>REGULAR</strong> stock.</span>
        <span>Daily, return, and weekly figures show — until transaction data is recorded.</span>
      </div>
    </div>
  );
}
