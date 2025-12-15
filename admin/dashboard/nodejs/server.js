const express = require('express');
const cors = require('cors');
const forecastRoutes = require('./routes/forecast');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/forecast', forecastRoutes);

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', message: 'Forecast API is running' });
});

// Start server
app.listen(PORT, () => {
  console.log(`Forecast API server is running on port ${PORT}`);
  console.log(`Health check: http://localhost:${PORT}/health`);
  console.log(`Forecast endpoint: http://localhost:${PORT}/api/forecast?type=sales&filter=yearly`);
});

