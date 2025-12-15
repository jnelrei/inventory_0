# Dashboard Forecast API

Node.js API for generating forecast data for the dashboard charts.

## Installation

1. Navigate to the nodejs folder:
```bash
cd admin/dashboard/nodejs
```

2. Install dependencies:
```bash
npm install
```

## Configuration

Update the database configuration in `config/db.js` if needed:
- host: Database host (default: localhost)
- database: Database name (default: tci)
- user: Database user (default: root)
- password: Database password (default: empty)

## Running the Server

### Development mode (with auto-reload):
```bash
npm run dev
```

### Production mode:
```bash
npm start
```

The server will start on port 3001 by default.

## API Endpoints

### GET /api/forecast

Get forecast data for charts.

**Query Parameters:**
- `type` (required): Type of forecast data
  - `sales` - Sales forecast
  - `products` - Top products forecast
  - `stock` - Stock level forecast
  - `orders` - Orders forecast
- `filter` (optional): Time filter (default: daily)
  - `daily` - Daily forecast
  - `weekly` - Weekly forecast
  - `monthly` - Monthly forecast
  - `yearly` - Yearly forecast

**Example Requests:**
```
GET /api/forecast?type=sales&filter=yearly
GET /api/forecast?type=products&filter=yearly
GET /api/forecast?type=stock
GET /api/forecast?type=orders&filter=yearly
```

**Response:**
```json
{
  "success": true,
  "dates": ["January", "February", ...],
  "data": [10, 15, 20, ...],
  "labels": ["Product 1", "Product 2", ...]
}
```

## Health Check

```
GET /health
```

Returns server status.

## Updating PHP to Use Node.js API

To use this Node.js API instead of the PHP version, update the JavaScript in `adm_dashboard.php`:

Change:
```javascript
const url = isForecastMode 
  ? 'get_forecast_data.php?type=sales&filter=' + effectiveFilter
  : 'get_sales_data.php?filter=' + filter;
```

To:
```javascript
const url = isForecastMode 
  ? 'http://localhost:3001/api/forecast?type=sales&filter=' + effectiveFilter
  : 'get_sales_data.php?filter=' + filter;
```

## Dependencies

- express: Web framework
- mysql2: MySQL database driver
- cors: Cross-Origin Resource Sharing middleware
- nodemon: Development auto-reload (dev dependency)

