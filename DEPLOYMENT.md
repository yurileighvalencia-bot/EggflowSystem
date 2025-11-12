# Deployment Guide for EggFlow System

## Production Deployment Options

### Option 1: Deploy to Heroku (Backend) + Vercel (Frontend)

#### Backend on Heroku

1. Install Heroku CLI and login:
```bash
heroku login
```

2. Create Heroku app:
```bash
heroku create eggflow-system-backend
```

3. Add MongoDB Atlas add-on or configure external MongoDB:
```bash
# Option A: Use MongoDB Atlas
heroku addons:create mongolab:sandbox

# Option B: Use external MongoDB (recommended)
# Set environment variable with your MongoDB Atlas connection string
heroku config:set MONGODB_URI="mongodb+srv://username:password@cluster.mongodb.net/eggflowsystem"
```

4. Set environment variables:
```bash
heroku config:set JWT_SECRET="your_production_jwt_secret_very_secure"
heroku config:set NODE_ENV="production"
heroku config:set FRONTEND_URL="https://your-frontend-domain.vercel.app"
heroku config:set LOW_STOCK_THRESHOLD=100
heroku config:set AUTO_CANCEL_INTERVAL=3600000
```

5. Create Procfile in root:
```
web: node server.js
```

6. Deploy:
```bash
git add .
git commit -m "Prepare for Heroku deployment"
git push heroku main
```

#### Frontend on Vercel

1. Install Vercel CLI:
```bash
npm i -g vercel
```

2. Navigate to frontend directory:
```bash
cd frontend
```

3. Create `vercel.json`:
```json
{
  "rewrites": [
    { "source": "/(.*)", "destination": "/index.html" }
  ]
}
```

4. Update `vite.config.js` for production:
```javascript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000
  },
  build: {
    outDir: 'dist'
  }
})
```

5. Update API base URL in AuthContext.jsx and other files:
```javascript
// Use environment variable for API URL
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:5000';
axios.defaults.baseURL = API_URL;
```

6. Deploy to Vercel:
```bash
vercel
```

7. Set environment variable in Vercel dashboard:
```
VITE_API_URL=https://your-heroku-backend.herokuapp.com
```

### Option 2: Deploy to VPS (DigitalOcean, AWS, etc.)

#### 1. Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install MongoDB
# Follow: https://docs.mongodb.com/manual/installation/

# Install PM2 for process management
sudo npm install -g pm2

# Install Nginx
sudo apt install -y nginx
```

#### 2. Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/yourusername/EggflowSystem.git
cd EggflowSystem
sudo npm install
cd frontend
sudo npm install
sudo npm run build
```

#### 3. Configure Environment

```bash
# Create production .env file
sudo nano .env
```

Set production values:
```env
PORT=5000
NODE_ENV=production
MONGODB_URI=mongodb://localhost:27017/eggflowsystem
JWT_SECRET=your_very_secure_production_secret_key
FRONTEND_URL=https://yourdomain.com
LOW_STOCK_THRESHOLD=100
AUTO_CANCEL_INTERVAL=3600000
```

#### 4. Start Backend with PM2

```bash
pm2 start server.js --name eggflow-backend
pm2 save
pm2 startup
```

#### 5. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/eggflow
```

Add configuration:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    # Frontend
    location / {
        root /var/www/EggflowSystem/frontend/dist;
        try_files $uri $uri/ /index.html;
    }

    # Backend API
    location /api {
        proxy_pass http://localhost:5000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Socket.io
    location /socket.io {
        proxy_pass http://localhost:5000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/eggflow /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 6. Setup SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Option 3: Docker Deployment

#### 1. Create Dockerfile for Backend

```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 5000

CMD ["node", "server.js"]
```

#### 2. Create Dockerfile for Frontend

```dockerfile
FROM node:18-alpine as build

WORKDIR /app

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

#### 3. Create docker-compose.yml

```yaml
version: '3.8'

services:
  mongodb:
    image: mongo:6
    restart: always
    volumes:
      - mongo-data:/data/db
    environment:
      MONGO_INITDB_DATABASE: eggflowsystem

  backend:
    build: .
    restart: always
    ports:
      - "5000:5000"
    environment:
      - PORT=5000
      - MONGODB_URI=mongodb://mongodb:27017/eggflowsystem
      - JWT_SECRET=${JWT_SECRET}
      - NODE_ENV=production
      - FRONTEND_URL=http://localhost:3000
    depends_on:
      - mongodb

  frontend:
    build:
      context: .
      dockerfile: Dockerfile.frontend
    restart: always
    ports:
      - "80:80"
    depends_on:
      - backend

volumes:
  mongo-data:
```

#### 4. Deploy with Docker Compose

```bash
docker-compose up -d
```

## MongoDB Atlas Setup (Recommended for Production)

1. Create account at https://www.mongodb.com/cloud/atlas

2. Create a new cluster (Free tier available)

3. Create database user:
   - Database Access → Add New Database User
   - Set username and password

4. Whitelist IP addresses:
   - Network Access → Add IP Address
   - For development: Add current IP
   - For production: Add server IP or 0.0.0.0/0 (allow from anywhere)

5. Get connection string:
   - Clusters → Connect → Connect your application
   - Copy connection string
   - Replace `<password>` with your database user password

6. Update MONGODB_URI in .env:
```
MONGODB_URI=mongodb+srv://username:password@cluster0.xxxxx.mongodb.net/eggflowsystem?retryWrites=true&w=majority
```

## Security Checklist for Production

- [ ] Change JWT_SECRET to a strong, random value
- [ ] Use HTTPS (SSL certificate)
- [ ] Enable CORS only for trusted domains
- [ ] Use environment variables for all secrets
- [ ] Set NODE_ENV=production
- [ ] Enable rate limiting (consider using express-rate-limit)
- [ ] Keep dependencies updated
- [ ] Use strong MongoDB credentials
- [ ] Enable MongoDB authentication
- [ ] Regular backups of database
- [ ] Monitor server logs
- [ ] Set up error tracking (e.g., Sentry)

## Environment Variables Summary

Required for production:

```env
# Server
PORT=5000
NODE_ENV=production

# Database
MONGODB_URI=your_mongodb_connection_string

# Security
JWT_SECRET=your_very_secure_random_secret_key

# Frontend
FRONTEND_URL=https://yourdomain.com

# Application Settings
LOW_STOCK_THRESHOLD=100
AUTO_CANCEL_INTERVAL=3600000
```

## Monitoring and Maintenance

### Setup Monitoring

1. PM2 monitoring:
```bash
pm2 install pm2-logrotate
pm2 set pm2-logrotate:max_size 10M
pm2 set pm2-logrotate:retain 7
```

2. Monitor application:
```bash
pm2 monit
pm2 logs eggflow-backend
```

### Backup Strategy

1. MongoDB backups:
```bash
# Create backup script
mongodump --uri="mongodb://localhost:27017/eggflowsystem" --out=/backups/$(date +%Y%m%d)

# Schedule with cron
0 2 * * * /usr/bin/mongodump --uri="mongodb://localhost:27017/eggflowsystem" --out=/backups/$(date +\%Y\%m\%d)
```

2. Restore from backup:
```bash
mongorestore --uri="mongodb://localhost:27017/eggflowsystem" /backups/20240101
```

### Update Deployment

```bash
# Pull latest changes
cd /var/www/EggflowSystem
git pull origin main

# Update backend
npm install

# Update frontend
cd frontend
npm install
npm run build

# Restart application
pm2 restart eggflow-backend
```

## Troubleshooting

### Backend not starting
- Check PM2 logs: `pm2 logs eggflow-backend`
- Verify MongoDB connection
- Check .env file exists and has correct values

### Frontend not loading
- Check Nginx configuration
- Verify build directory exists: `ls frontend/dist`
- Check Nginx logs: `sudo tail -f /var/log/nginx/error.log`

### Database connection issues
- Verify MongoDB is running: `sudo systemctl status mongod`
- Check MongoDB URI format
- Verify network access (for Atlas)

### Socket.io connection failed
- Ensure WebSocket support in Nginx
- Check CORS settings
- Verify backend URL in frontend code
