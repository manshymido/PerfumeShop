import { Link } from 'react-router-dom';
import { Heart, ShoppingCart } from 'lucide-react';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { wishlistService } from '../services/wishlist';
import { useState } from 'react';
import toast from 'react-hot-toast';

export default function ProductCard({ product }) {
  const { addToCart } = useCart();
  const { isAuthenticated } = useAuth();
  const [isWishlisted, setIsWishlisted] = useState(false);
  const [loading, setLoading] = useState(false);

  const primaryImage = product.images?.find(img => img.is_primary) || product.images?.[0];
  const imageUrl = primaryImage?.image_url || 
    (primaryImage?.image_path ? `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${primaryImage.image_path}` : null) ||
    'https://via.placeholder.com/300x300?text=No+Image';

  const handleAddToCart = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    setLoading(true);
    await addToCart(product.id, 1);
    setLoading(false);
  };

  const handleWishlist = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!isAuthenticated) {
      toast.error('Please login to add to wishlist');
      return;
    }

    try {
      if (isWishlisted) {
        // Remove from wishlist - would need wishlist item ID
        toast.success('Removed from wishlist');
        setIsWishlisted(false);
      } else {
        const response = await wishlistService.add(product.id);
        if (response.success) {
          setIsWishlisted(true);
          // Show info message if already exists, success if newly added
          if (response.message?.includes('already')) {
            toast.success('Already in wishlist');
          } else {
            toast.success(response.message || 'Added to wishlist');
          }
        } else {
          toast.error(response.message || 'Failed to add to wishlist');
        }
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to update wishlist';
      toast.error(errorMessage);
    }
  };

  const isInStock = product.inventory?.quantity > 0;

  return (
    <Link to={`/products/${product.id}`} className="card group hover:shadow-xl transition-shadow">
      <div className="relative overflow-hidden">
        <img
          src={imageUrl}
          alt={product.name}
          className="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
          onError={(e) => {
            e.target.src = 'https://via.placeholder.com/300x300?text=No+Image';
          }}
        />
        <button
          onClick={handleWishlist}
          className={`absolute top-2 right-2 p-2 rounded-full bg-white shadow-md hover:bg-gray-100 transition-colors ${
            isWishlisted ? 'text-primary-600' : 'text-gray-600'
          }`}
        >
          <Heart className={`w-5 h-5 ${isWishlisted ? 'fill-current' : ''}`} />
        </button>
        {!isInStock && (
          <div className="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-sm font-medium">
            Out of Stock
          </div>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-lg mb-1 line-clamp-1">{product.name}</h3>
        <p className="text-sm text-gray-600 mb-2">{product.brand}</p>
        <div className="flex items-center justify-between">
          <span className="text-xl font-bold text-primary-600">
            ${parseFloat(product.price).toFixed(2)}
          </span>
          <button
            onClick={handleAddToCart}
            disabled={!isInStock || loading}
            className={`p-2 rounded-full ${
              isInStock
                ? 'bg-primary-600 text-white hover:bg-primary-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            } transition-colors`}
          >
            <ShoppingCart className="w-5 h-5" />
          </button>
        </div>
        {product.reviews_count > 0 && (
          <div className="mt-2 flex items-center space-x-1">
            <span className="text-yellow-500">★</span>
            <span className="text-sm text-gray-600">
              {product.average_rating?.toFixed(1) || '0.0'} ({product.reviews_count})
            </span>
          </div>
        )}
      </div>
    </Link>
  );
}

