import api from './api';

export const categoryService = {
  getAll: async () => {
    const response = await api.get('/v1/categories');
    return response.data;
  },

  getById: async (id) => {
    const response = await api.get(`/v1/categories/${id}`);
    return response.data;
  },
};

