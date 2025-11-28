import api from './api';

export const shippingService = {
  getAll: async () => {
    const response = await api.get('/v1/shipping-addresses');
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/v1/shipping-addresses/${id}`);
    return response.data;
  },

  create: async (data) => {
    const response = await api.post('/v1/shipping-addresses', data);
    return response.data;
  },

  update: async (id, data) => {
    const response = await api.put(`/v1/shipping-addresses/${id}`, data);
    return response.data;
  },

  delete: async (id) => {
    const response = await api.delete(`/v1/shipping-addresses/${id}`);
    return response.data;
  },
};

