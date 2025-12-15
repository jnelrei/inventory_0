const express = require('express');
const router = express.Router();
const pool = require('../config/db');

// Helper function to get date range text
function getDateRangeText(filter) {
  const now = new Date();
  let dateText = '';

  switch (filter) {
    case 'daily':
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const monthName = monthNames[now.getMonth()];
      const day = now.getDate();
      dateText = `(${monthName} ${day})`;
      break;

    case 'weekly':
      const currentDay = now.getDay();
      const daysSinceSunday = currentDay;
      const weekStart = new Date(now);
      weekStart.setDate(now.getDate() - daysSinceSunday);
      const weekEnd = new Date(weekStart);
      weekEnd.setDate(weekStart.getDate() + 6);
      
      const startMonth = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][weekStart.getMonth()];
      const endMonth = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][weekEnd.getMonth()];
      dateText = `(${startMonth} ${weekStart.getDate()} to ${endMonth} ${weekEnd.getDate()})`;
      break;

    case 'monthly':
      const monthNameMonthly = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][now.getMonth()];
      const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
      dateText = `(${monthNameMonthly} 1 to ${monthNameMonthly} ${lastDay})`;
      break;

    case 'yearly':
      const fullMonthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                              'July', 'August', 'September', 'October', 'November', 'December'];
      const currentMonth = fullMonthNames[now.getMonth()];
      const currentYear = now.getFullYear();
      dateText = `(${currentMonth} ${currentYear})`;
      break;

    default:
      dateText = '';
  }

  return dateText;
}

// Helper function to format time (matches PHP date('g A') format)
function formatTime(hour) {
  const date = new Date();
  date.setHours(hour, 0, 0, 0);
  // Format: "1 AM", "2 PM", etc. (no leading zero, 12-hour format)
  const hour12 = date.getHours() % 12 || 12;
  const ampm = date.getHours() >= 12 ? 'PM' : 'AM';
  return `${hour12} ${ampm}`;
}

// Helper function to parse items from sales
function parseItems(itemsString) {
  const productCounts = {};
  
  if (!itemsString || itemsString.trim() === '') {
    return productCounts;
  }

  const items = itemsString.split(',');
  
  for (const item of items) {
    const trimmedItem = item.trim();
    if (!trimmedItem) continue;

    const match = trimmedItem.match(/^(.+?)\s*x\s*(\d+)$/i);
    let itemName, quantity;

    if (match) {
      itemName = match[1].trim();
      quantity = parseInt(match[2], 10);
    } else {
      itemName = trimmedItem;
      quantity = 1;
    }

    if (productCounts[itemName]) {
      productCounts[itemName] += quantity;
    } else {
      productCounts[itemName] = quantity;
    }
  }

  return productCounts;
}

// GET /api/forecast
router.get('/', async (req, res) => {
  const type = req.query.type || 'sales'; // sales, products, stock, orders
  const filter = req.query.filter || 'daily';

  const dates = [];
  const data = [];
  const labels = [];

  try {
    switch (type) {
      case 'sales':
        // Forecast sales based on historical patterns
        switch (filter) {
          case 'daily':
            // Get historical hourly averages from past 7 days
            const [dailySales] = await pool.query(`
              SELECT 
                HOUR(created_at) as hour_time,
                AVG(transaction_count) as avg_count
              FROM (
                SELECT 
                  HOUR(created_at) as hour_time,
                  DATE(created_at) as sale_date,
                  COUNT(*) as transaction_count
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY HOUR(created_at), DATE(created_at)
              ) as daily_hours
              GROUP BY hour_time
              ORDER BY hour_time ASC
            `);

            const hourlyAvg = Array(24).fill(0);
            for (const row of dailySales) {
              const hour = parseInt(row.hour_time, 10);
              hourlyAvg[hour] = Math.round(row.avg_count * 1.1); // 10% growth forecast
            }

            for (let i = 0; i < 24; i++) {
              dates.push(formatTime(i));
              data.push(Math.max(0, hourlyAvg[i]));
            }
            break;

          case 'weekly':
            // Get historical weekly patterns from past 4 weeks
            const [weeklySales] = await pool.query(`
              SELECT 
                DAYOFWEEK(created_at) as day_of_week,
                AVG(transaction_count) as avg_count
              FROM (
                SELECT 
                  DAYOFWEEK(created_at) as day_of_week,
                  DATE(created_at) as sale_date,
                  COUNT(*) as transaction_count
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
                GROUP BY DAYOFWEEK(created_at), DATE(created_at)
              ) as weekly_days
              GROUP BY day_of_week
              ORDER BY day_of_week ASC
            `);

            const dayAvg = Array(8).fill(0); // Index 0 unused, 1-7 for days
            for (const row of weeklySales) {
              const day = parseInt(row.day_of_week, 10);
              dayAvg[day] = Math.round(row.avg_count * 1.1);
            }

            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            for (let i = 1; i <= 7; i++) {
              dates.push(dayNames[i - 1]);
              data.push(Math.max(0, dayAvg[i]));
            }
            break;

          case 'monthly':
            // Get historical daily averages from past 3 months
            const [monthlySales] = await pool.query(`
              SELECT 
                DAY(created_at) as day_num,
                AVG(transaction_count) as avg_count
              FROM (
                SELECT 
                  DAY(created_at) as day_num,
                  DATE(created_at) as sale_date,
                  COUNT(*) as transaction_count
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY DAY(created_at), DATE(created_at)
              ) as monthly_days
              GROUP BY day_num
              ORDER BY day_num ASC
            `);

            const now = new Date();
            const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
            const monthlyAvg = Array(daysInMonth + 1).fill(0);

            for (const row of monthlySales) {
              const day = parseInt(row.day_num, 10);
              if (day <= daysInMonth) {
                monthlyAvg[day] = Math.round(row.avg_count * 1.1);
              }
            }

            const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            for (let day = 1; day <= daysInMonth; day++) {
              const date = new Date(`${currentMonth}-${String(day).padStart(2, '0')}`);
              dates.push(`${monthNames[date.getMonth()]} ${day}`);
              data.push(Math.max(0, monthlyAvg[day]));
            }
            break;

          case 'yearly':
            // Get historical monthly averages from past 2 years
            const [yearlySales] = await pool.query(`
              SELECT 
                MONTH(created_at) as month_num,
                AVG(transaction_count) as avg_count
              FROM (
                SELECT 
                  MONTH(created_at) as month_num,
                  YEAR(created_at) as sale_year,
                  COUNT(*) as transaction_count
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                GROUP BY MONTH(created_at), YEAR(created_at)
              ) as yearly_months
              GROUP BY month_num
              ORDER BY month_num ASC
            `);

            const yearlyAvg = Array(13).fill(0); // Index 0 unused, 1-12 for months
            for (const row of yearlySales) {
              const month = parseInt(row.month_num, 10);
              yearlyAvg[month] = Math.round(row.avg_count * 1.15); // 15% growth forecast
            }

            const monthNamesFull = ['January', 'February', 'March', 'April', 'May', 'June', 
                                    'July', 'August', 'September', 'October', 'November', 'December'];
            for (let month = 1; month <= 12; month++) {
              dates.push(monthNamesFull[month - 1]);
              data.push(Math.max(0, yearlyAvg[month]));
            }
            break;
        }
        break;

      case 'products':
        // Forecast top products based on historical trends
        const [productSales] = await pool.query(`
          SELECT items FROM sales 
          WHERE items IS NOT NULL AND items != '' 
          AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        `);

        const productCounts = {};
        for (const sale of productSales) {
          const parsed = parseItems(sale.items);
          for (const [itemName, quantity] of Object.entries(parsed)) {
            if (productCounts[itemName]) {
              productCounts[itemName] += quantity;
            } else {
              productCounts[itemName] = quantity;
            }
          }
        }

        // Sort and get top 5
        const sortedProducts = Object.entries(productCounts)
          .sort(([, a], [, b]) => b - a)
          .slice(0, 5);

        for (const [product, count] of sortedProducts) {
          labels.push(product);
          data.push(Math.max(1, Math.round(count * 1.2))); // 20% growth forecast
        }
        break;

      case 'stock':
        // Forecast stock levels (use current stock as base, apply trend)
        const [stockData] = await pool.query(`
          SELECT 
            item_name,
            quantity,
            (SELECT SUM(quantity) FROM sales s WHERE s.items LIKE CONCAT('%', i.item_name, '%') AND DATE(s.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as recent_sales
          FROM invtry i
          ORDER BY quantity DESC
          LIMIT 8
        `);

        for (const row of stockData) {
          labels.push(row.item_name);
          const currentStock = parseInt(row.quantity, 10);
          const recentSales = parseInt(row.recent_sales || 0, 10);
          
          // Forecast: reduce stock based on sales trend, but keep minimum
          const forecastStock = Math.max(0, currentStock - Math.round(recentSales * 0.3));
          data.push(forecastStock);
        }
        break;

      case 'orders':
        // Forecast orders similar to sales
        switch (filter) {
          case 'daily':
            const [dailyOrders] = await pool.query(`
              SELECT 
                HOUR(created_at) as hour_time,
                AVG(order_quantity) as avg_quantity
              FROM (
                SELECT 
                  HOUR(created_at) as hour_time,
                  DATE(created_at) as order_date,
                  SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(items, 'x', -1), ',', 1) AS UNSIGNED)) as order_quantity
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND items IS NOT NULL AND items != ''
                GROUP BY HOUR(created_at), DATE(created_at)
              ) as daily_hours
              GROUP BY hour_time
              ORDER BY hour_time ASC
            `);

            const hourlyOrderAvg = Array(24).fill(0);
            for (const row of dailyOrders) {
              const hour = parseInt(row.hour_time, 10);
              hourlyOrderAvg[hour] = Math.round(row.avg_quantity * 1.1);
            }

            for (let i = 0; i < 24; i++) {
              dates.push(formatTime(i));
              data.push(Math.max(0, hourlyOrderAvg[i]));
            }
            break;

          case 'weekly':
            const [weeklyOrders] = await pool.query(`
              SELECT 
                DAYOFWEEK(created_at) as day_of_week,
                AVG(order_quantity) as avg_quantity
              FROM (
                SELECT 
                  DAYOFWEEK(created_at) as day_of_week,
                  DATE(created_at) as order_date,
                  SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(items, 'x', -1), ',', 1) AS UNSIGNED)) as order_quantity
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
                AND items IS NOT NULL AND items != ''
                GROUP BY DAYOFWEEK(created_at), DATE(created_at)
              ) as weekly_days
              GROUP BY day_of_week
              ORDER BY day_of_week ASC
            `);

            const dayOrderAvg = Array(8).fill(0);
            for (const row of weeklyOrders) {
              const day = parseInt(row.day_of_week, 10);
              dayOrderAvg[day] = Math.round(row.avg_quantity * 1.1);
            }

            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            for (let i = 1; i <= 7; i++) {
              dates.push(dayNames[i - 1]);
              data.push(Math.max(0, dayOrderAvg[i]));
            }
            break;

          case 'monthly':
            const [monthlyOrders] = await pool.query(`
              SELECT 
                DAY(created_at) as day_num,
                AVG(order_quantity) as avg_quantity
              FROM (
                SELECT 
                  DAY(created_at) as day_num,
                  DATE(created_at) as order_date,
                  SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(items, 'x', -1), ',', 1) AS UNSIGNED)) as order_quantity
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                AND items IS NOT NULL AND items != ''
                GROUP BY DAY(created_at), DATE(created_at)
              ) as monthly_days
              GROUP BY day_num
              ORDER BY day_num ASC
            `);

            const now = new Date();
            const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
            const monthlyOrderAvg = Array(daysInMonth + 1).fill(0);

            for (const row of monthlyOrders) {
              const day = parseInt(row.day_num, 10);
              if (day <= daysInMonth) {
                monthlyOrderAvg[day] = Math.round(row.avg_quantity * 1.1);
              }
            }

            const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            
            for (let day = 1; day <= daysInMonth; day++) {
              const date = new Date(`${currentMonth}-${String(day).padStart(2, '0')}`);
              dates.push(`${monthNames[date.getMonth()]} ${day}`);
              data.push(Math.max(0, monthlyOrderAvg[day]));
            }
            break;

          case 'yearly':
            const [yearlyOrders] = await pool.query(`
              SELECT 
                MONTH(created_at) as month_num,
                AVG(order_quantity) as avg_quantity
              FROM (
                SELECT 
                  MONTH(created_at) as month_num,
                  YEAR(created_at) as order_year,
                  SUM(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(items, 'x', -1), ',', 1) AS UNSIGNED)) as order_quantity
                FROM sales 
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
                AND items IS NOT NULL AND items != ''
                GROUP BY MONTH(created_at), YEAR(created_at)
              ) as yearly_months
              GROUP BY month_num
              ORDER BY month_num ASC
            `);

            const yearlyOrderAvg = Array(13).fill(0);
            for (const row of yearlyOrders) {
              const month = parseInt(row.month_num, 10);
              yearlyOrderAvg[month] = Math.round(row.avg_quantity * 1.15);
            }

            const monthNamesFull = ['January', 'February', 'March', 'April', 'May', 'June', 
                                    'July', 'August', 'September', 'October', 'November', 'December'];
            for (let month = 1; month <= 12; month++) {
              dates.push(monthNamesFull[month - 1]);
              data.push(Math.max(0, yearlyOrderAvg[month]));
            }
            break;
        }
        break;
    }

    res.json({
      success: true,
      dates: dates,
      data: data,
      labels: labels
    });

  } catch (error) {
    console.error('Error fetching forecast data:', error);
    res.status(500).json({
      success: false,
      message: 'Error fetching forecast data: ' + error.message,
      dates: [],
      data: [],
      labels: []
    });
  }
});

module.exports = router;

