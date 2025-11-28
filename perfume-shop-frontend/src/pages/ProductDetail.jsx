import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { productService } from '../services/products';
import { reviewService } from '../services/reviews';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { wishlistService } from '../services/wishlist';
import { Heart, ShoppingCart, Star, Check } from 'lucide-react';
import toast from 'react-hot-toast';

export default function ProductDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { addToCart } = useCart();
  const { isAuthenticated } = useAuth();
  const [product, setProduct] = useState(null);
  const [reviews, setReviews] = useState([]);
  const [reviewStats, setReviewStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [isWishlisted, setIsWishlisted] = useState(false);
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [showReviewForm, setShowReviewForm] = useState(false);
  const [reviewForm, setReviewForm] = useState({ rating: 5, comment: '' });

  useEffect(() => {
    loadProduct();
    loadReviews();
  }, [id]);

  const loadProduct = async () => {
    try {
      setLoading(true);
      const response = await productService.getById(id);
      if (response.success) {
        setProduct(response.data.product);
      }
    } catch (error) {
      console.error('Failed to load product:', error);
      toast.error('Product not found');
      navigate('/products');
    } finally {
      setLoading(false);
    }
  };

  const loadReviews = async () => {
    try {
      const response = await reviewService.getByProduct(id);
      if (response.success) {
        // API returns: { success: true, data: { reviews: [...], statistics: {...}, meta: {...} } }
        // ReviewResource::collection() serializes to an array directly
        setReviews(response.data.reviews || []);
        setReviewStats(response.data.statistics);
      }
    } catch (error) {
      console.error('Failed to load reviews:', error);
      toast.error('Failed to load reviews');
    }
  };

  const handleAddToCart = async () => {
    await addToCart(product.id, quantity);
  };

  const handleWishlist = async () => {
    if (!isAuthenticated) {
      toast.error('Please login to add to wishlist');
      navigate('/login');
      return;
    }

    try {
      if (isWishlisted) {
        toast.success('Removed from wishlist');
        setIsWishlisted(false);
      } else {
        const response = await wishlistService.add(product.id);
        if (response.success) {
          setIsWishlisted(true);
          toast.success(response.message || 'Added to wishlist');
        } else {
          toast.error(response.message || 'Failed to add to wishlist');
        }
      }
    } catch (error) {
      const errorMessage = error.response?.data?.message || 'Failed to update wishlist';
      toast.error(errorMessage);
    }
  };

  const handleSubmitReview = async (e) => {
    e.preventDefault();
    if (!isAuthenticated) {
      toast.error('Please login to submit a review');
      return;
    }

    try {
      const response = await reviewService.create(id, reviewForm);
      if (response.success) {
        toast.success('Review submitted successfully');
        setShowReviewForm(false);
        setReviewForm({ rating: 5, comment: '' });
        loadReviews();
        loadProduct();
      }
    } catch (error) {
      const errorData = error.response?.data;
      let errorMessage = 'Failed to submit review';
      
      if (errorData?.errors) {
        const firstError = Object.values(errorData.errors)[0];
        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
      } else if (errorData?.message) {
        errorMessage = errorData.message;
      }
      
      toast.error(errorMessage);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!product) {
    return null;
  }

  const images = product.images || [];
  const primaryImage = images.find(img => img.is_primary) || images[0];
  const imageUrl = primaryImage?.image_url || 
    (primaryImage?.image_path ? `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${primaryImage.image_path}` : null) ||
    'https://via.placeholder.com/600x600?text=No+Image';

  const isInStock = product.inventory?.quantity > 0;
  const averageRating = product.average_rating || reviewStats?.average_rating || 0;
  const reviewsCount = product.reviews_count || reviewStats?.total_reviews || 0;

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
        {/* Product Images */}
        <div>
          <div className="mb-4">
            <img
              src={images[activeImageIndex]?.image_url || 
                (images[activeImageIndex]?.image_path ? `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${images[activeImageIndex].image_path}` : null) ||
                imageUrl}
              alt={product.name}
              className="w-full h-96 object-cover rounded-lg"
              onError={(e) => {
                e.target.src = 'https://via.placeholder.com/600x600?text=No+Image';
              }}
            />
          </div>
          {images.length > 1 && (
            <div className="flex space-x-2">
              {images.map((img, index) => (
                <button
                  key={index}
                  onClick={() => setActiveImageIndex(index)}
                  className={`w-20 h-20 rounded-lg overflow-hidden border-2 ${
                    activeImageIndex === index ? 'border-primary-600' : 'border-gray-300'
                  }`}
                >
                  <img
                    src={img.image_url || 
                      (img.image_path ? `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/storage/${img.image_path}` : null) ||
                      imageUrl}
                    alt={`${product.name} ${index + 1}`}
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.target.src = 'https://via.placeholder.com/150x150?text=No+Image';
                    }}
                  />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Product Info */}
        <div>
          <h1 className="text-4xl font-bold mb-2">{product.name}</h1>
          <p className="text-xl text-gray-600 mb-4">{product.brand}</p>

          {/* Rating */}
          {reviewsCount > 0 && (
            <div className="flex items-center space-x-2 mb-4">
              <div className="flex items-center">
                {[1, 2, 3, 4, 5].map((star) => (
                  <Star
                    key={star}
                    className={`w-5 h-5 ${
                      star <= Math.round(averageRating)
                        ? 'text-yellow-400 fill-current'
                        : 'text-gray-300'
                    }`}
                  />
                ))}
              </div>
              <span className="text-gray-600">
                {averageRating.toFixed(1)} ({reviewsCount} reviews)
              </span>
            </div>
          )}

          <div className="text-3xl font-bold text-primary-600 mb-6">
            ${parseFloat(product.price).toFixed(2)}
          </div>

          <p className="text-gray-700 mb-6">{product.description}</p>

          {/* Product Details */}
          {product.fragrance_notes && (
            <div className="mb-6">
              <h3 className="font-semibold mb-2">Fragrance Notes:</h3>
              <p className="text-gray-600">{product.fragrance_notes}</p>
            </div>
          )}

          {product.size && (
            <div className="mb-6">
              <h3 className="font-semibold mb-2">Size:</h3>
              <p className="text-gray-600">{product.size}</p>
            </div>
          )}

          {/* Stock Status */}
          <div className="mb-6">
            {isInStock ? (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <Check className="w-4 h-4 mr-1" />
                In Stock ({product.inventory.quantity} available)
              </span>
            ) : (
              <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                Out of Stock
              </span>
            )}
          </div>

          {/* Quantity and Actions */}
          <div className="flex items-center space-x-4 mb-6">
            <div className="flex items-center space-x-2">
              <label htmlFor="product_quantity" className="font-medium">Quantity:</label>
              <input
                id="product_quantity"
                name="quantity"
                type="number"
                min="1"
                max={product.inventory?.quantity || 1}
                autoComplete="off"
                value={quantity}
                onChange={(e) => setQuantity(parseInt(e.target.value) || 1)}
                className="w-20 input-field"
                disabled={!isInStock}
              />
            </div>
          </div>

          <div className="flex space-x-4">
            <button
              onClick={handleAddToCart}
              disabled={!isInStock}
              className={`flex-1 btn-primary flex items-center justify-center space-x-2 ${
                !isInStock ? 'opacity-50 cursor-not-allowed' : ''
              }`}
            >
              <ShoppingCart className="w-5 h-5" />
              <span>Add to Cart</span>
            </button>
            <button
              onClick={handleWishlist}
              className={`p-4 rounded-lg border-2 ${
                isWishlisted
                  ? 'border-primary-600 bg-primary-50 text-primary-600'
                  : 'border-gray-300 hover:border-primary-600'
              } transition-colors`}
            >
              <Heart className={`w-6 h-6 ${isWishlisted ? 'fill-current' : ''}`} />
            </button>
          </div>
        </div>
      </div>

      {/* Reviews Section */}
      <div className="mt-16">
        <div className="flex items-center justify-between mb-8">
          <h2 className="text-2xl font-bold">Reviews</h2>
          {isAuthenticated && (
            <button
              onClick={() => setShowReviewForm(!showReviewForm)}
              className="btn-primary"
            >
              Write a Review
            </button>
          )}
        </div>

        {/* Review Form */}
        {showReviewForm && (
          <form onSubmit={handleSubmitReview} className="card p-6 mb-8">
            <h3 className="text-lg font-semibold mb-4">Write a Review</h3>
            <div className="mb-4">
              <label className="block font-medium mb-2">Rating</label>
              <div className="flex space-x-1">
                {[1, 2, 3, 4, 5].map((star) => (
                  <button
                    key={star}
                    type="button"
                    onClick={() => setReviewForm({ ...reviewForm, rating: star })}
                    className="focus:outline-none"
                  >
                    <Star
                      className={`w-8 h-8 ${
                        star <= reviewForm.rating
                          ? 'text-yellow-400 fill-current'
                          : 'text-gray-300'
                      }`}
                    />
                  </button>
                ))}
              </div>
            </div>
            <div className="mb-4">
              <label htmlFor="review_comment" className="block font-medium mb-2">Comment</label>
              <textarea
                id="review_comment"
                name="comment"
                autoComplete="off"
                value={reviewForm.comment}
                onChange={(e) => setReviewForm({ ...reviewForm, comment: e.target.value })}
                rows="4"
                className="input-field"
                placeholder="Share your thoughts about this product..."
              />
            </div>
            <div className="flex space-x-4">
              <button type="submit" className="btn-primary">
                Submit Review
              </button>
              <button
                type="button"
                onClick={() => setShowReviewForm(false)}
                className="btn-secondary"
              >
                Cancel
              </button>
            </div>
          </form>
        )}

        {/* Reviews List */}
        {reviews.length === 0 ? (
          <p className="text-gray-600">No reviews yet. Be the first to review!</p>
        ) : (
          <div className="space-y-6">
            {reviews.map((review) => (
              <div key={review.id} className="card p-6">
                <div className="flex items-start justify-between mb-2">
                  <div>
                    <h4 className="font-semibold">{review.user?.name || 'Anonymous'}</h4>
                    <div className="flex items-center space-x-2">
                      <div className="flex">
                        {[1, 2, 3, 4, 5].map((star) => (
                          <Star
                            key={star}
                            className={`w-4 h-4 ${
                              star <= review.rating
                                ? 'text-yellow-400 fill-current'
                                : 'text-gray-300'
                            }`}
                          />
                        ))}
                      </div>
                      {review.verified_purchase && (
                        <span className="text-xs text-green-600 font-medium">
                          Verified Purchase
                        </span>
                      )}
                    </div>
                  </div>
                  <span className="text-sm text-gray-500">
                    {new Date(review.created_at).toLocaleDateString()}
                  </span>
                </div>
                {review.comment && (
                  <p className="text-gray-700 mt-2">{review.comment}</p>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

