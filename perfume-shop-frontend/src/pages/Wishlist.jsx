import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { wishlistService } from '../services/wishlist';
import { useCart } from '../context/CartContext';
import { Heart, ShoppingCart, Trash2 } from 'lucide-react';
import toast from 'react-hot-toast';

export default function Wishlist() {
  const [wishlistItems, setWishlistItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const { addToCart } = useCart();

  useEffect(() => {
    loadWishlist();
  }, []);

  const loadWishlist = async () => {
    try {
      setLoading(true);
      const response = await wishlistService.getAll();
      if (response.success) {
        setWishlistItems(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load wishlist:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRemove = async (wishlistItemId) => {
    try {
      const response = await wishlistService.remove(wishlistItemId);
      if (response.success) {
        toast.success('Removed from wishlist');
        loadWishlist();
      }
    } catch (error) {
      toast.error('Failed to remove from wishlist');
    }
  };

  const handleMoveToCart = async (wishlistItemId) => {
    try {
      const response = await wishlistService.moveToCart(wishlistItemId);
      if (response.success) {
        toast.success('Moved to cart');
        loadWishlist();
      }
    } catch (error) {
      toast.error('Failed to move to cart');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (wishlistItems.length === 0) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="text-center">
          <Heart className="w-24 h-24 text-gray-300 mx-auto mb-4" />
          <h2 className="text-2xl font-bold mb-2">Your wishlist is empty</h2>
          <p className="text-gray-600 mb-8">Start adding products to your wishlist</p>
          <Link to="/products" className="btn-primary inline-block">
            Start Shopping
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold mb-8">My Wishlist</h1>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {wishlistItems.map((item) => {
          const product = item.product;
          const primaryImage = product?.images?.find(img => img.is_primary) || product?.images?.[0];
          const imageUrl = primaryImage?.image_url || 
            (primaryImage?.image_path ? `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${primaryImage.image_path}` : null) ||
            'https://via.placeholder.com/300x300?text=No+Image';

          return (
            <div key={item.id} className="card group hover:shadow-xl transition-shadow">
              <div className="relative overflow-hidden">
                <Link to={`/products/${product?.id}`}>
                  <img
                    src={imageUrl}
                    alt={product?.name}
                    className="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                    onError={(e) => {
                      e.target.src = 'https://via.placeholder.com/300x300?text=No+Image';
                    }}
                  />
                </Link>
                <button
                  onClick={() => handleRemove(item.id)}
                  className="absolute top-2 right-2 p-2 rounded-full bg-white shadow-md hover:bg-red-50 text-red-600 transition-colors"
                >
                  <Trash2 className="w-5 h-5" />
                </button>
              </div>
              <div className="p-4">
                <Link to={`/products/${product?.id}`}>
                  <h3 className="font-semibold text-lg mb-1 line-clamp-1 hover:text-primary-600">
                    {product?.name}
                  </h3>
                </Link>
                <p className="text-sm text-gray-600 mb-2">{product?.brand}</p>
                <div className="flex items-center justify-between">
                  <span className="text-xl font-bold text-primary-600">
                    ${parseFloat(product?.price || 0).toFixed(2)}
                  </span>
                  <button
                    onClick={() => handleMoveToCart(item.id)}
                    className="p-2 rounded-full bg-primary-600 text-white hover:bg-primary-700 transition-colors"
                    title="Add to cart"
                  >
                    <ShoppingCart className="w-5 h-5" />
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

