import api from './api';

export const orderService = {
  getAll: async () => {
    const response = await api.get('/v1/orders');
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/v1/orders/${id}`);
    return response.data;
  },

  create: async (paymentIntentId, shippingAddressId) => {
    const response = await api.post('/v1/orders', {
      payment_intent_id: paymentIntentId,
      shipping_address_id: shippingAddressId,
    });
    return response.data;
  },

  cancel: async (id) => {
    const response = await api.put(`/v1/orders/${id}/cancel`);
    return response.data;
  },

  getInvoice: async (id) => {
    const response = await api.get(`/v1/orders/${id}/invoice`);
    return response.data;
  },
};

