import api from './api';

export const productService = {
  getAll: async (params = {}) => {
    const response = await api.get('/v1/products', { params });
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/v1/products/${id}`);
    return response.data;
  },

  getByCategory: async (categoryId, params = {}) => {
    const response = await api.get(`/v1/products/category/${categoryId}`, { params });
    return response.data;
  },

  getRecommended: async () => {
    const response = await api.get('/v1/products/recommended');
    return response.data;
  },

  getRecentlyViewed: async () => {
    const response = await api.get('/v1/products/recently-viewed');
    return response.data;
  },
};

