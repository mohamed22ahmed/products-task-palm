import React, { useState } from 'react';
import './ProductComponent.css';

const ProductComponent = ({ product, onViewDetails, showDetailsButton = true }) => {
  const [showDetails, setShowDetails] = useState(false);

  const handleViewDetails = () => {
    setShowDetails(true);
    if (onViewDetails) {
      onViewDetails(product.id);
    }
  };

  const handleCloseDetails = () => {
    setShowDetails(false);
  };

  return (
    <div className="product-card">
      {product.image_url && (
        <div className="product-image-container">
          <img 
            src={product.image_url}
            alt={product.title}
            className="product-image"
          />
        </div>
      )}
      
      <div className="product-header">
        <h3 className="product-title">{product.title}</h3>
        <span className="product-price">{product.price}</span>
      </div>
      
      {showDetailsButton && (
        <button 
          className="details-button" 
          onClick={handleViewDetails}
        >
          View Details
        </button>
      )}

      {showDetails && (
        <div className="product-details-modal">
          <div className="modal-content">
            <button className="close-button" onClick={handleCloseDetails}>×</button>
            <h2>Product Details</h2>
            
            {product.image_url && (
              <div className="detail-image-container">
                <img 
                  src={product.image_url}
                  alt={product.title}
                  className="detail-image"
                />
              </div>
            )}
            
            <div className="detail-item">
              <strong>ID:</strong> {product.id}
            </div>
            <div className="detail-item">
              <strong>Title:</strong> {product.title}
            </div>
            {product.createdAt && (
              <div className="detail-item">
                <strong>Created:</strong> {new Date(product.createdAt).toLocaleDateString()}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductComponent;
