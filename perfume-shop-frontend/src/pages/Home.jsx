import { useEffect, useState, useRef } from 'react';
import { Link } from 'react-router-dom';
import { productService } from '../services/products';
import { categoryService } from '../services/categories';
import ProductCard from '../components/ProductCard';
import { Sparkles } from 'lucide-react';

export default function Home() {
  const [featuredProducts, setFeaturedProducts] = useState([]);
  const [recommendedProducts, setRecommendedProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;

    const loadData = async () => {
      try {
        const [recommendedRes, categoriesRes] = await Promise.all([
          productService.getRecommended(),
          categoryService.getAll(),
        ]);

        // Only update state if component is still mounted
        if (isMountedRef.current) {
        if (recommendedRes.success) {
          setRecommendedProducts(recommendedRes.data || []);
          setFeaturedProducts(recommendedRes.data?.slice(0, 4) || []);
        }

        if (categoriesRes.success) {
          setCategories(categoriesRes.data || []);
          }
          setLoading(false);
        }
      } catch (error) {
        if (isMountedRef.current) {
        console.error('Failed to load home data:', error);
        setLoading(false);
        }
      }
    };

    loadData();

    // Cleanup function
    return () => {
      isMountedRef.current = false;
    };
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-5xl font-bold mb-4">
              Discover Your Signature Scent
            </h1>
            <p className="text-xl mb-8 text-primary-100">
              Explore our curated collection of luxury fragrances
            </p>
            <Link to="/products" className="btn-primary bg-white text-primary-600 hover:bg-gray-100 inline-block">
              Shop Now
            </Link>
          </div>
        </div>
      </section>

      {/* Categories Section */}
      {categories.length > 0 && (
        <section className="py-12 bg-white">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 className="text-3xl font-bold mb-8 text-center">Shop by Category</h2>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {categories.map((category) => (
                <Link
                  key={category.id}
                  to={`/products?category_id=${category.id}`}
                  className="card p-6 text-center hover:shadow-lg transition-shadow"
                >
                  <div className="text-4xl mb-4">🌸</div>
                  <h3 className="font-semibold text-lg">{category.name}</h3>
                  <p className="text-sm text-gray-600 mt-2">
                    {category.products_count || 0} products
                  </p>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Featured Products */}
      {featuredProducts.length > 0 && (
        <section className="py-12 bg-gray-50">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-3xl font-bold">Featured Products</h2>
              <Link to="/products" className="text-primary-600 hover:text-primary-700 font-medium">
                View All →
              </Link>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {featuredProducts.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Recommended Products */}
      {recommendedProducts.length > 0 && (
        <section className="py-12 bg-white">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center space-x-2 mb-8">
              <Sparkles className="w-8 h-8 text-primary-600" />
              <h2 className="text-3xl font-bold">Recommended for You</h2>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {recommendedProducts.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          </div>
        </section>
      )}
    </div>
  );
}

