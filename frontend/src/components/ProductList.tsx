import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api, useAuth } from '../contexts/AuthContext';
import { ShoppingCart, LogOut, Package, AlertCircle, CheckCircle2 } from 'lucide-react';

interface Product {
  id: number;
  name: string;
  stock: number;
  description?: string;
  price?: number;
  image_url?: string;
}

export default function ProductList() {
  const { user, logout } = useAuth();
  const queryClient = useQueryClient();
  const [notification, setNotification] = useState<{message: string, type: 'success'|'error'} | null>(null);

  const { data: products, isLoading, error } = useQuery({
    queryKey: ['products'],
    queryFn: async () => {
      const response = await api.get('/products');
      return response.data as Product[];
    }
  });

  const bookMutation = useMutation({
    mutationFn: async ({ productId, quantity }: { productId: number, quantity: number }) => {
      const response = await api.post(`/v1/products/${productId}/book`, { quantity });
      return response.data;
    },
    // Optimistic Update
    onMutate: async (newBooking) => {
      await queryClient.cancelQueries({ queryKey: ['products'] });
      const previousProducts = queryClient.getQueryData<Product[]>(['products']);

      if (previousProducts) {
        queryClient.setQueryData<Product[]>(['products'], old => {
          if (!old) return [];
          return old.map(p => 
            p.id === newBooking.productId 
              ? { ...p, stock: p.stock - newBooking.quantity }
              : p
          );
        });
      }
      return { previousProducts };
    },
    onError: (err: any, newBooking, context) => {
      // Rollback on error
      if (context?.previousProducts) {
        queryClient.setQueryData(['products'], context.previousProducts);
      }
      setNotification({
        message: err.response?.data?.error || err.response?.data?.message || 'Booking failed. Someone else might have grabbed the last unit.',
        type: 'error'
      });
      setTimeout(() => setNotification(null), 5000);
    },
    onSuccess: (data) => {
      setNotification({ message: data.message || 'Booking successful!', type: 'success' });
      setTimeout(() => setNotification(null), 5000);
    },
    onSettled: () => {
      // Refetch to sync with server
      queryClient.invalidateQueries({ queryKey: ['products'] });
    }
  });

  const handleBook = (productId: number) => {
    bookMutation.mutate({ productId, quantity: 1 });
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="bg-red-50 text-red-600 p-6 rounded-xl flex items-center shadow-sm border border-red-100">
          <AlertCircle className="mr-3 h-6 w-6" />
          <span>Failed to load products. Please try again.</span>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16 items-center">
            <div className="flex items-center text-brand-600 font-bold text-xl">
              <Package className="mr-2 h-6 w-6" />
              Chetak Bookings
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-gray-600 font-medium">Hello, {user?.name}</span>
              <button
                onClick={logout}
                className="flex items-center text-gray-500 hover:text-red-500 transition-colors"
              >
                <LogOut className="h-5 w-5 mr-1" />
                Logout
              </button>
            </div>
          </div>
        </div>
      </nav>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        {notification && (
          <div className={`mb-8 p-4 rounded-xl flex items-center shadow-sm border ${notification.type === 'success' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-red-50 text-red-700 border-red-100'} transition-all`}>
            {notification.type === 'success' ? <CheckCircle2 className="mr-3 h-5 w-5" /> : <AlertCircle className="mr-3 h-5 w-5" />}
            {notification.message}
          </div>
        )}

        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">Available Products</h1>
          <p className="mt-2 text-gray-500">Book your items quickly before stock runs out.</p>
        </div>

        {products?.length === 0 ? (
          <div className="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100">
            <Package className="mx-auto h-12 w-12 text-gray-300" />
            <h3 className="mt-4 text-lg font-medium text-gray-900">No products available</h3>
            <p className="mt-2 text-gray-500">Check back later for new inventory.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            {products?.map((product) => (
              <div key={product.id} className="bg-white flex flex-col rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl transition-shadow duration-300 group">
                {product.image_url ? (
                  <div className="relative w-full h-48 bg-gray-200 overflow-hidden">
                    <img 
                      src={product.image_url} 
                      alt={product.name} 
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    />
                  </div>
                ) : (
                  <div className="w-full h-48 bg-brand-50 flex items-center justify-center">
                    <Package className="h-12 w-12 text-brand-200" />
                  </div>
                )}
                
                <div className="p-6 flex flex-col flex-grow">
                  <div className="flex justify-between items-start mb-2">
                    <h3 className="text-xl font-bold text-gray-900 group-hover:text-brand-600 transition-colors">{product.name}</h3>
                    {product.price && (
                      <span className="text-lg font-bold text-brand-600">${product.price}</span>
                    )}
                  </div>
                  
                  {product.description && (
                    <p className="text-gray-500 text-sm mb-4 line-clamp-2 flex-grow">
                      {product.description}
                    </p>
                  )}
                  
                  <div className="mt-auto flex items-center mb-6">
                    <span className="text-sm text-gray-500 mr-2">Current Stock:</span>
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${product.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                      {product.stock > 0 ? `${product.stock} available` : 'Out of stock'}
                    </span>
                  </div>

                  <button
                    onClick={() => handleBook(product.id)}
                    disabled={product.stock <= 0 || bookMutation.isPending}
                    className="w-full flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-95"
                  >
                    <ShoppingCart className="mr-2 h-5 w-5" />
                    {bookMutation.isPending && bookMutation.variables?.productId === product.id ? 'Booking...' : 'Book Now'}
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
