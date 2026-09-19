import React from 'react';
import { useAuth } from '../contexts/AuthContext';
import { LogOut, Package, ListOrdered } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';

export default function Layout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth();
  const location = useLocation();

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <nav className="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16 items-center">
            <div className="flex items-center space-x-8">
              <div className="flex items-center text-brand-600 font-bold text-xl">
                <Package className="mr-2 h-6 w-6" />
                Chetak Bookings
              </div>
              
              {user && (
                <div className="hidden sm:flex space-x-4">
                  <Link 
                    to="/" 
                    className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${location.pathname === '/' ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}`}
                  >
                    <Package className="inline-block w-4 h-4 mr-1.5 mb-0.5" />
                    Products
                  </Link>
                  <Link 
                    to="/orders" 
                    className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${location.pathname === '/orders' ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}`}
                  >
                    <ListOrdered className="inline-block w-4 h-4 mr-1.5 mb-0.5" />
                    My Orders
                  </Link>
                </div>
              )}
            </div>
            
            {user && (
              <div className="flex items-center space-x-4">
                <span className="text-gray-600 font-medium hidden sm:inline-block">Hello, {user.name}</span>
                <button
                  onClick={logout}
                  className="flex items-center text-gray-500 hover:text-red-500 transition-colors"
                >
                  <LogOut className="h-5 w-5 sm:mr-1" />
                  <span className="hidden sm:inline-block">Logout</span>
                </button>
              </div>
            )}
          </div>
        </div>
      </nav>

      <main className="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">
        {children}
      </main>
    </div>
  );
}
