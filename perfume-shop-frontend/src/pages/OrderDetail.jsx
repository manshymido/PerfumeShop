import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { orderService } from '../services/orders';
import { MapPin, Package, Calendar, X } from 'lucide-react';
import toast from 'react-hot-toast';

export default function OrderDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadOrder();
  }, [id]);

  const loadOrder = async () => {
    try {
      setLoading(true);
      const response = await orderService.getById(id);
      if (response.success) {
        setOrder(response.data);
      }
    } catch (error) {
      console.error('Failed to load order:', error);
      toast.error('Order not found');
      navigate('/orders');
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = async () => {
    if (!window.confirm('Are you sure you want to cancel this order?')) {
      return;
    }

    try {
      const response = await orderService.cancel(id);
      if (response.success) {
        toast.success('Order cancelled successfully');
        loadOrder();
      }
    } catch (error) {
      toast.error('Failed to cancel order');
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      processing: 'bg-blue-100 text-blue-800',
      shipped: 'bg-purple-100 text-purple-800',
      delivered: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const canCancel = order && ['pending', 'processing'].includes(order.status);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!order) {
    return null;
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-2">Order #{order.id}</h1>
        <div className="flex items-center space-x-4">
          <span
            className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(
              order.status
            )}`}
          >
            {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
          </span>
          <span className="text-gray-600 flex items-center space-x-1">
            <Calendar className="w-4 h-4" />
            <span>Placed on {new Date(order.created_at).toLocaleDateString()}</span>
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Order Items */}
        <div className="lg:col-span-2 space-y-6">
          <div className="card p-6">
            <h2 className="text-xl font-semibold mb-4 flex items-center space-x-2">
              <Package className="w-5 h-5" />
              <span>Order Items</span>
            </h2>
            <div className="space-y-4">
              {order.order_items?.map((item) => {
                const product = item.product;
                const primaryImage = product?.images?.find(img => img.is_primary) || product?.images?.[0];
                const imageUrl = primaryImage?.image_url || 
                  (primaryImage?.image_path ? `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${primaryImage.image_path}` : null) ||
                  'https://via.placeholder.com/100x100?text=No+Image';

                return (
                  <div key={item.id} className="flex gap-4 pb-4 border-b last:border-0">
                    <img
                      src={imageUrl}
                      alt={product?.name}
                      className="w-20 h-20 object-cover rounded-lg"
                      onError={(e) => {
                        e.target.src = 'https://via.placeholder.com/100x100?text=No+Image';
                      }}
                    />
                    <div className="flex-1">
                      <h3 className="font-semibold">{product?.name}</h3>
                      <p className="text-sm text-gray-600">{product?.brand}</p>
                      <p className="text-sm text-gray-600 mt-1">
                        Quantity: {item.quantity} × ${parseFloat(item.price).toFixed(2)}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="font-semibold">
                        ${(parseFloat(item.price) * item.quantity).toFixed(2)}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Shipping Address */}
          {order.shipping_address && (
            <div className="card p-6">
              <h2 className="text-xl font-semibold mb-4 flex items-center space-x-2">
                <MapPin className="w-5 h-5" />
                <span>Shipping Address</span>
              </h2>
              <div className="text-gray-700">
                <p className="font-semibold">{order.shipping_address.full_name}</p>
                <p>{order.shipping_address.address}</p>
                <p>
                  {order.shipping_address.city}, {order.shipping_address.state}{' '}
                  {order.shipping_address.zip}
                </p>
                <p>{order.shipping_address.country}</p>
                <p className="mt-2">Phone: {order.shipping_address.phone}</p>
              </div>
            </div>
          )}

          {/* Status History */}
          {order.status_history && order.status_history.length > 0 && (
            <div className="card p-6">
              <h2 className="text-xl font-semibold mb-4">Order Status History</h2>
              <div className="space-y-3">
                {order.status_history.map((history, index) => (
                  <div key={index} className="flex items-start space-x-3">
                    <div className="flex-shrink-0 w-2 h-2 rounded-full bg-primary-600 mt-2"></div>
                    <div className="flex-1">
                      <p className="font-medium">{history.status}</p>
                      {history.notes && (
                        <p className="text-sm text-gray-600">{history.notes}</p>
                      )}
                      <p className="text-xs text-gray-500 mt-1">
                        {new Date(history.created_at).toLocaleString()}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Order Summary */}
        <div className="lg:col-span-1">
          <div className="card p-6 sticky top-24">
            <h2 className="text-xl font-bold mb-4">Order Summary</h2>
            <div className="space-y-2 mb-4">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span>${parseFloat(order.subtotal).toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Tax</span>
                <span>${parseFloat(order.tax).toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Shipping</span>
                <span>${parseFloat(order.shipping_cost).toFixed(2)}</span>
              </div>
              <div className="border-t pt-2 flex justify-between font-bold text-lg">
                <span>Total</span>
                <span>${parseFloat(order.total).toFixed(2)}</span>
              </div>
            </div>

            {order.tracking_number && (
              <div className="mb-4 p-3 bg-gray-50 rounded-lg">
                <p className="text-sm font-medium mb-1">Tracking Number</p>
                <p className="text-lg font-mono">{order.tracking_number}</p>
              </div>
            )}

            {canCancel && (
              <button
                onClick={handleCancel}
                className="w-full btn-secondary text-red-600 hover:bg-red-50 flex items-center justify-center space-x-2"
              >
                <X className="w-5 h-5" />
                <span>Cancel Order</span>
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

