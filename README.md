# products-task-palm
### contians backend and frontend
## Backend
 - Laravel framework
 - MySQL
 - API
 - Scraping
 - Guzzle
 - Proxy Management

## Frontend
 - React
 - CSS
 - you don't need to refresh the page to see the changes, cause i call the API to get the products every 30 seconds

## Setup Project
 - into product-backend directory
   - composer install
   - npm install
   - php artisan migrate --seed    // using seed here to create initial patterns data that used to extract data
   - php artisan serve    // to run the backend on port 8000

 - into product-frontend directory
   - npm install
   - npm start to run the frontend on port 3000
   - to access the whole products use http://localhost:3000/ from the browser

### You can find the endpoints in:
   - palm-api.postman_collection.json
   - please import the collection to postman to be able to scrape and get products
   - the endpoints are:
      - http://localhost:8000/api/v1/products  GET
      - http://localhost:8000/api/v1/products/{id} GET
      - http://localhost:8000/api/v1/products/scrape POST

I created the service of proxy management using PHP cause i don't know Golang and i don't want to use AI to write the service.
