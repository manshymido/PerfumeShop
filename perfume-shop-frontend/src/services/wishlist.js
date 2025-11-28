import api from './api';

export const wishlistService = {
  getAll: async () => {
    const response = await api.get('/v1/wishlist');
    return response.data;
  },

  add: async (productId) => {
    const response = await api.post('/v1/wishlist', {
      product_id: productId,
    });
    return response.data;
  },

  remove: async (wishlistItemId) => {
    const response = await api.delete(`/v1/wishlist/${wishlistItemId}`);
    return response.data;
  },

  moveToCart: async (wishlistItemId) => {
    const response = await api.post(`/v1/wishlist/${wishlistItemId}/move-to-cart`);
    return response.data;
  },
};

