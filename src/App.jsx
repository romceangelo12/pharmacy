import React, { useState, useEffect } from 'react';
import Login from './components/Login';
import Register from './components/Register';
import Dashboard from './components/Dashboard';

export default function App() {
  const [view, setView] = useState('login'); // 'login' | 'register' | 'dashboard'
  const [user, setUser] = useState(null);
  const [checkingAuth, setCheckingAuth] = useState(true);

  useEffect(() => {
    fetch('api/auth.php?action=me')
      .then((res) => res.json())
      .then((data) => {
        if (data.authenticated && data.user) {
          setUser(data.user);
          setView('dashboard');
        }
      })
      .catch((err) => console.log('Auth check error', err))
      .finally(() => setCheckingAuth(false));
  }, []);

  const handleLoginSuccess = (userData) => {
    setUser(userData);
    setView('dashboard');
  };

  const handleLogout = async () => {
    try {
      await fetch('api/auth.php?action=logout', { method: 'POST' });
    } catch (e) {}
    setUser(null);
    setView('login');
  };

  if (checkingAuth) {
    return (
      <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b' }}>
        Loading JVGH Pharmacy...
      </div>
    );
  }

  if (view === 'dashboard' && user) {
    return <Dashboard user={user} onLogout={handleLogout} />;
  }

  if (view === 'register') {
    return <Register onRegisterSuccess={() => setView('login')} onNavigateToLogin={() => setView('login')} />;
  }

  return <Login onLoginSuccess={handleLoginSuccess} onNavigateToRegister={() => setView('register')} />;
}

