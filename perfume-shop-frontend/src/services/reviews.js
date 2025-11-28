import api from './api';

export const reviewService = {
  getByProduct: async (productId, params = {}) => {
    const response = await api.get(`/v1/products/${productId}/reviews`, { params });
    return response.data;
  },

  create: async (productId, data) => {
    const response = await api.post(`/v1/products/${productId}/reviews`, data);
    return response.data;
  },

  update: async (reviewId, data) => {
    const response = await api.put(`/v1/reviews/${reviewId}`, data);
    return response.data;
  },

  delete: async (reviewId) => {
    const response = await api.delete(`/v1/reviews/${reviewId}`);
    return response.data;
  },
};

