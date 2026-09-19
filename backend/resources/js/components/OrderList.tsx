import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '../contexts/AuthContext';
import { Clock, CheckCircle2, XCircle, Package } from 'lucide-react';

interface Order {
  id: number;
  quantity: number;
  status: 'pending' | 'confirmed' | 'failed';
  created_at: string;
  product?: {
    name: string;
    image_url?: string;
  };
}

export default function OrderList() {
  const { data: orders, isLoading, error } = useQuery({
    queryKey: ['orders'],
    queryFn: async () => {
      const response = await api.get('/v1/orders');
      return response.data.data as Order[];
    },
    // Poll every 3 seconds to see live status updates
    refetchInterval: 3000,
  });

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-20">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 text-red-600 p-6 rounded-xl text-center shadow-sm border border-red-100">
        Failed to load orders.
      </div>
    );
  }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">My Orders</h1>
        <p className="mt-2 text-gray-500">Track your bookings and payment status in real-time.</p>
      </div>

      {!orders || orders.length === 0 ? (
        <div className="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100">
          <Package className="mx-auto h-12 w-12 text-gray-300" />
          <h3 className="mt-4 text-lg font-medium text-gray-900">No orders yet</h3>
          <p className="mt-2 text-gray-500">Book a product to see it here.</p>
        </div>
      ) : (
        <div className="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
          <ul className="divide-y divide-gray-100">
            {orders.map((order) => (
              <li key={order.id} className="p-6 hover:bg-gray-50 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    {order.product?.image_url ? (
                      <img src={order.product.image_url} alt={order.product?.name} className="h-16 w-16 rounded-xl object-cover border border-gray-100" />
                    ) : (
                      <div className="h-16 w-16 bg-gray-100 rounded-xl flex items-center justify-center border border-gray-200">
                        <Package className="h-8 w-8 text-gray-400" />
                      </div>
                    )}
                    <div>
                      <h4 className="text-lg font-bold text-gray-900">{order.product?.name || 'Unknown Product'}</h4>
                      <p className="text-sm text-gray-500 mt-1">
                        Qty: <span className="font-medium text-gray-900">{order.quantity}</span> • Ordered on {new Date(order.created_at).toLocaleDateString()}
                      </p>
                    </div>
                  </div>
                  <div>
                    {order.status === 'pending' && (
                      <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 shadow-sm">
                        <Clock className="mr-1.5 h-4 w-4" /> Processing...
                      </span>
                    )}
                    {order.status === 'confirmed' && (
                      <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 shadow-sm">
                        <CheckCircle2 className="mr-1.5 h-4 w-4" /> Confirmed
                      </span>
                    )}
                    {order.status === 'failed' && (
                      <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 shadow-sm">
                        <XCircle className="mr-1.5 h-4 w-4" /> Payment Failed
                      </span>
                    )}
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
