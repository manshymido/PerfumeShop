import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { productService } from '../services/products';
import { categoryService } from '../services/categories';
import ProductCard from '../components/ProductCard';
import { Filter, X } from 'lucide-react';
import toast from 'react-hot-toast';

export default function Products() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    category_id: searchParams.get('category_id') || '',
    q: searchParams.get('q') || '',
    min_price: '',
    max_price: '',
    brand: '',
    sort_by: 'created_at',
    sort_order: 'desc',
  });
  const [showFilters, setShowFilters] = useState(false);
  const [pagination, setPagination] = useState({});

  useEffect(() => {
    // Sync filters with searchParams
    setFilters({
      category_id: searchParams.get('category_id') || '',
      q: searchParams.get('q') || '',
      min_price: searchParams.get('min_price') || '',
      max_price: searchParams.get('max_price') || '',
      brand: searchParams.get('brand') || '',
      sort_by: searchParams.get('sort_by') || 'created_at',
      sort_order: searchParams.get('sort_order') || 'desc',
    });
  }, [searchParams]);

  useEffect(() => {
    loadProducts();
    loadCategories();
  }, [searchParams]);

  const loadProducts = async () => {
    try {
      setLoading(true);
      const params = {
        category_id: searchParams.get('category_id') || '',
        q: searchParams.get('q') || '',
        min_price: searchParams.get('min_price') || '',
        max_price: searchParams.get('max_price') || '',
        brand: searchParams.get('brand') || '',
        sort_by: searchParams.get('sort_by') || 'created_at',
        sort_order: searchParams.get('sort_order') || 'desc',
        page: searchParams.get('page') || 1,
      };
      
      // Remove empty filters
      Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null) {
          delete params[key];
        }
      });

      const response = await productService.getAll(params);
      if (response.success) {
        // API returns { success: true, data: [...], meta: {...} }
        setProducts(response.data || []);
        setPagination({
          current_page: response.meta?.current_page || 1,
          last_page: response.meta?.last_page || 1,
          per_page: response.meta?.per_page || 15,
          total: response.meta?.total || 0,
        });
      }
    } catch (error) {
      console.error('Failed to load products:', error);
      toast.error('Failed to load products');
    } finally {
      setLoading(false);
    }
  };

  const loadCategories = async () => {
    try {
      const response = await categoryService.getAll();
      if (response.success) {
        setCategories(response.data || []);
      }
    } catch (error) {
      console.error('Failed to load categories:', error);
    }
  };

  const handleFilterChange = (key, value) => {
    const newFilters = { ...filters, [key]: value };
    setFilters(newFilters);
    
    // Update URL params
    const newParams = new URLSearchParams();
    Object.keys(newFilters).forEach(k => {
      if (newFilters[k]) {
        newParams.set(k, newFilters[k]);
      }
    });
    setSearchParams(newParams);
  };

  const clearFilters = () => {
    setFilters({
      category_id: '',
      q: '',
      min_price: '',
      max_price: '',
      brand: '',
      sort_by: 'created_at',
      sort_order: 'desc',
    });
    setSearchParams({});
  };

  const brands = [...new Set(products.map(p => p.brand).filter(Boolean))];

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-3xl font-bold">Products</h1>
        <button
          onClick={() => setShowFilters(!showFilters)}
          className="flex items-center space-x-2 btn-secondary md:hidden"
        >
          <Filter className="w-5 h-5" />
          <span>Filters</span>
        </button>
      </div>

      <div className="flex gap-8">
        {/* Sidebar Filters */}
        <aside className={`w-64 ${showFilters ? 'block' : 'hidden'} md:block`}>
          <div className="card p-6 sticky top-24">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold">Filters</h2>
              <button
                onClick={clearFilters}
                className="text-sm text-primary-600 hover:text-primary-700"
              >
                Clear All
              </button>
            </div>

            {/* Search */}
            <div className="mb-4">
              <label htmlFor="product_search" className="block text-sm font-medium mb-2">Search</label>
              <input
                id="product_search"
                name="q"
                type="text"
                autoComplete="off"
                value={filters.q}
                onChange={(e) => handleFilterChange('q', e.target.value)}
                placeholder="Search products..."
                className="input-field"
              />
            </div>

            {/* Category */}
            <div className="mb-4">
              <label htmlFor="product_category_filter" className="block text-sm font-medium mb-2">Category</label>
              <select
                id="product_category_filter"
                name="category_id"
                autoComplete="off"
                value={filters.category_id}
                onChange={(e) => handleFilterChange('category_id', e.target.value)}
                className="input-field"
              >
                <option value="">All Categories</option>
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.id}>
                    {cat.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Brand */}
            {brands.length > 0 && (
              <div className="mb-4">
                <label htmlFor="product_brand_filter" className="block text-sm font-medium mb-2">Brand</label>
                <select
                  id="product_brand_filter"
                  name="brand"
                  autoComplete="off"
                  value={filters.brand}
                  onChange={(e) => handleFilterChange('brand', e.target.value)}
                  className="input-field"
                >
                  <option value="">All Brands</option>
                  {brands.map((brand) => (
                    <option key={brand} value={brand}>
                      {brand}
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Price Range */}
            <div className="mb-4">
              <label className="block text-sm font-medium mb-2">Price Range</label>
              <div className="flex gap-2">
                <input
                  id="min_price"
                  name="min_price"
                  type="number"
                  autoComplete="off"
                  value={filters.min_price}
                  onChange={(e) => handleFilterChange('min_price', e.target.value)}
                  placeholder="Min"
                  className="input-field"
                />
                <input
                  id="max_price"
                  name="max_price"
                  type="number"
                  autoComplete="off"
                  value={filters.max_price}
                  onChange={(e) => handleFilterChange('max_price', e.target.value)}
                  placeholder="Max"
                  className="input-field"
                />
              </div>
            </div>

            {/* Sort */}
            <div className="mb-4">
              <label htmlFor="product_sort" className="block text-sm font-medium mb-2">Sort By</label>
              <select
                id="product_sort"
                name="sort"
                autoComplete="off"
                value={`${filters.sort_by}_${filters.sort_order}`}
                onChange={(e) => {
                  const [sort_by, sort_order] = e.target.value.split('_');
                  handleFilterChange('sort_by', sort_by);
                  handleFilterChange('sort_order', sort_order);
                }}
                className="input-field"
              >
                <option value="created_at_desc">Newest First</option>
                <option value="created_at_asc">Oldest First</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name_asc">Name: A to Z</option>
                <option value="name_desc">Name: Z to A</option>
              </select>
            </div>
          </div>
        </aside>

        {/* Products Grid */}
        <div className="flex-1">
          {loading ? (
            <div className="flex items-center justify-center py-20">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>
          ) : products.length === 0 ? (
            <div className="text-center py-20">
              <p className="text-gray-600 text-lg">No products found</p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {products.map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </div>

              {/* Pagination */}
              {pagination.last_page > 1 && (
                <div className="flex items-center justify-center space-x-2 mt-8">
                  <button
                    onClick={() => {
                      const page = Math.max(1, pagination.current_page - 1);
                      setSearchParams({ ...Object.fromEntries(searchParams), page });
                    }}
                    disabled={pagination.current_page === 1}
                    className="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <span className="px-4 py-2">
                    Page {pagination.current_page} of {pagination.last_page}
                  </span>
                  <button
                    onClick={() => {
                      const page = Math.min(pagination.last_page, pagination.current_page + 1);
                      setSearchParams({ ...Object.fromEntries(searchParams), page });
                    }}
                    disabled={pagination.current_page === pagination.last_page}
                    className="btn-secondary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}

