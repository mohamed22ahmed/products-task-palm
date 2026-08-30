import React, { useState, useEffect } from 'react';
import ProductComponent from '../components/ProductComponent';
import './ProductsPage.css';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8000/api/v1';

const ProductsPage = () => {
  const [products, setProducts] = useState([]);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch all products
  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE_URL}/products`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      setProducts(data.data || data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch products. Please try again later.');
      console.error('Error fetching products:', err);
    } finally {
      setLoading(false);
    }
  };

  // Fetch single product details
  const fetchProductDetails = async (productId) => {
    try {
      const response = await fetch(`${API_BASE_URL}/products/${productId}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      setSelectedProduct(data.data);
    } catch (err) {
      console.error('Error fetching product details:', err);
      setError('Failed to fetch product details.');
    }
  };

  const handleViewDetails = (productId) => {
    fetchProductDetails(productId);
  };

  const handleBackToList = () => {
    setSelectedProduct(null);
  };

  if (loading) {
    return (
      <div className="products-page">
        <div className="loading">Loading products...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="products-page">
        <div className="error">
          <p>{error}</p>
          <button onClick={fetchProducts} className="retry-button">Retry</button>
        </div>
      </div>
    );
  }

  if (selectedProduct) {
    return (
      <div className="products-page">
        <button onClick={handleBackToList} className="back-button">
          ← Back to Products
        </button>
        <ProductComponent 
          product={selectedProduct} 
          onViewDetails={handleViewDetails}
          showDetailsButton={false}
        />
      </div>
    );
  }

  return (
    <div className="products-page">
      <div className="products-header">
        <h1>Products</h1>
        <p>Showing {products.length} products</p>
      </div>
      
      {products.length === 0 ? (
        <div className="no-products">
          <p>No products available.</p>
        </div>
      ) : (
        <div className="products-grid">
          {products.map(product => (
            <ProductComponent 
              key={product.id} 
              product={product} 
              onViewDetails={handleViewDetails}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default ProductsPage;
