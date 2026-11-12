import React from 'react';

export default function Sidebar({ user, onLogout, activeTab, setActiveTab, stats = {}, isOpen, isCollapsed, onToggleCollapse, onClose }) {
  return (
    <aside className={`app-sidebar ${isOpen ? 'open' : ''} ${isCollapsed ? 'collapsed' : ''}`} id="appSidebar">
      {/* Brand Header */}
      <div className="sidebar-header">
        <div className="sidebar-logo">
          <img src="./logo.jpg" alt="JVGH Pharmacy logo" />
        </div>
        <div className="sidebar-brand">
          <div className="sidebar-brand-name">JVGH Pharmacy</div>
          <span className="sidebar-brand-sub">Inventory System</span>
        </div>
        <button
          type="button"
          className="sidebar-collapse-btn"
          onClick={onToggleCollapse}
          aria-label={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          title={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <polyline points={isCollapsed ? '9 18 15 12 9 6' : '15 18 9 12 15 6'}></polyline>
          </svg>
        </button>
      </div>

      {/* Navigation Menu */}
      <nav className="sidebar-nav">
        <div className="nav-section-title">Navigation</div>

        {/* 1. Dashboard */}
        <a
          href="#dashboard"
          className={`nav-item ${activeTab === 'dashboard' ? 'active' : ''}`}
          aria-label="Dashboard"
          title="Dashboard"
          onClick={(e) => {
            e.preventDefault();
            setActiveTab('dashboard');
            if (onClose) onClose();
          }}
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
          <span>Dashboard</span>
        </a>

        {/* 2. Regular */}
        <a
          href="#regular"
          className={`nav-item ${activeTab === 'regular' ? 'active' : ''}`}
          aria-label="Regular"
          title="Regular"
          onClick={(e) => {
            e.preventDefault();
            setActiveTab('regular');
            if (onClose) onClose();
          }}
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
            <path d="m8.5 8.5 7 7"></path>
          </svg>
          <span>Regular</span>
          <span className="nav-badge nav-badge-neutral">{stats.regular ?? 0}</span>
        </a>

        {/* 3. Donation */}
        <a
          href="#donation"
          className={`nav-item ${activeTab === 'donation' ? 'active' : ''}`}
          aria-label="Donation"
          title="Donation"
          onClick={(e) => {
            e.preventDefault();
            setActiveTab('donation');
            if (onClose) onClose();
          }}
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
          </svg>
          <span>Donation</span>
          <span className="nav-badge nav-badge-neutral">{stats.donation ?? 0}</span>
        </a>

        {/* 4. MHMAP */}
        <a
          href="#mhmap"
          className={`nav-item ${activeTab === 'mhmap' ? 'active' : ''}`}
          aria-label="MHMAP"
          title="MHMAP"
          onClick={(e) => {
            e.preventDefault();
            setActiveTab('mhmap');
            if (onClose) onClose();
          }}
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
          </svg>
          <span>MHMAP</span>
          <span className="nav-badge nav-badge-neutral">{stats.mhmap ?? 0}</span>
        </a>
      </nav>

      {/* User Footer */}
      <div className="sidebar-footer">
        <div className="user-profile-card">
          <div className="user-avatar-circle">
            {(user.full_name || 'U').charAt(0).toUpperCase()}
          </div>
          <div className="user-meta">
            <div className="user-meta-name">{user.full_name || user.username}</div>
            <div className="user-meta-role">{(user.role || '').replace('_', ' ')}</div>
          </div>
          <button onClick={onLogout} className="logout-icon-btn" title="Sign out" style={{ background: 'none', border: 'none', cursor: 'pointer' }}>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
          </button>
        </div>
      </div>
    </aside>
  );
}
