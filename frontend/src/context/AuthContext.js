import { createContext, useContext, useMemo, useState } from 'react';
import { login as apiLogin, register as apiRegister } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem('bbbms_token'));
  const [user, setUser] = useState(() => {
    const saved = localStorage.getItem('bbbms_user');
    return saved ? JSON.parse(saved) : null;
  });

  const login = async (email, password) => {
    const { data } = await apiLogin(email, password);
    localStorage.setItem('bbbms_token', data.access_token);
    localStorage.setItem('bbbms_user', JSON.stringify(data.user));
    setToken(data.access_token);
    setUser(data.user);
    return data.user;
  };

  const register = async (payload) => {
    await apiRegister(payload);
  };

  const logout = () => {
    localStorage.removeItem('bbbms_token');
    localStorage.removeItem('bbbms_user');
    setToken(null);
    setUser(null);
  };

  const value = useMemo(
    () => ({ token, user, login, register, logout, isAuthenticated: Boolean(token) }),
    [token, user]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
