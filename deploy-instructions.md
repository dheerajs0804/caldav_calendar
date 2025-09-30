# 🚀 CalDAV Calendar App - Linux Server Deployment

## 📋 Prerequisites

Before deploying, ensure your Linux server has:

1. **Docker** installed
2. **AWS CLI** installed and configured
3. **Docker Compose** installed (or docker compose plugin)
4. **ECR permissions** for user `mithiresearch-ecr-dev-user1`

## 🔧 Setup Instructions

### 1. Install Docker (if not installed)
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apt-transport-https ca-certificates curl gnupg lsb-release
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-compose-plugin

# CentOS/RHEL
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo yum install docker-compose -y
```

### 2. Install AWS CLI (if not installed)
```bash
# Ubuntu/Debian
sudo apt install awscli

# CentOS/RHEL
sudo yum install awscli

# Or using pip
pip install awscli
```

### 3. Configure AWS Credentials
```bash
aws configure
# Enter your AWS Access Key ID, Secret Access Key, Region (us-west-2)
```

### 4. Test ECR Access
```bash
aws ecr describe-repositories --region us-west-2
```

## 🚀 Deployment Options

### Option 1: Automated Deployment (Simple)

```bash
# Download and run simple deployment script
wget https://raw.githubusercontent.com/your-repo/deploy-apps/simple-deploy.sh
chmod +x simple-deploy.sh
./simple-deploy.sh
```

### Option 2: Manual Deployment (Step-by-step)

```bash
# 1. Authenticate with ECR
aws ecr get-login-password --region us-west-2 | docker login --username AWS --password-stdin 248189916187.dkr.ecr.us-west-2.amazonaws.com

# 2. Pull images
docker pull 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-frontend:latest
docker pull 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-backend:latest

# 3. Create docker-compose.yml
cat > docker-compose.yml << EOF
version: '3.8'

services:
  angular-dev:
    image: 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-frontend:latest
    ports:
      - "4200:4200"
    environment:
      - NODE_ENV=development
      - API_URL=http://php-backend:80
    networks:
      - caldev-network

  php-backend:
    image: 248189916187.dkr.ecr.us-west-2.amazonaws.com/caldev-backend:latest
    ports:
      - "8000:80"
    environment:
      - PHP_ENV=development
    networks:
      - caldev-network

networks:
  caldev-network:
    driver: bridge
EOF

# 4. Start the application
docker compose up -d angular-dev php-backend

# 5. Check status
docker compose ps
```

## 🔍 Post-Deployment

### Access the Application
- **Frontend**: http://YOUR_SERVER_IP:4200
- **Backend API**: http://YOUR_SERVER_IP:8000
- **Local Frontend**: http://localhost:4200
- **Local Backend**: http://localhost:8000

### Management Commands
```bash
# View logs
docker compose logs -f

# Stop application
docker compose down

# Restart application
docker compose restart

# Update to latest version
docker compose pull && docker compose up -d

# View container status
docker compose ps
```

### Health Checks
```bash
# Test frontend
curl http://localhost:4200

# Test backend
curl http://localhost:8000

# Check container logs
docker logs caldev-calendar_angular-dev_1
docker logs caldev-calendar_php-backend_1
```

## 🛠️ Troubleshooting

### Common Issues

1. **ECR Permission Denied**
   ```bash
   # Solution: Add ECR permissions to IAM user
   # Attach policy: AmazonEC2ContainerRegistryFullAccess
   ```

2. **Port Conflicts**
   ```bash
   # Check if ports are in use
   sudo netstat -tlnp | grep :4200
   sudo netstat -tlnp | grep :8000
   
  中# Kill processes using ports
   sudo kill -9 $(sudo lsof -t -i:4200)
   sudo kill -9 $(sudo lsof -t -i:8000)
   ```

3. **Container Won't Start**
   ```bash
   # Check container logs
   docker compose logs angular-dev
   docker compose logs php-backend
   
   # Restart containers
   docker compose restart
   ```

4. **Network Issues**
   ```bash
   # Check Docker network
   docker network ls
   docker network inspect caldev-calendar_caldev-network
   
   # Recreate network
   docker compose down
   docker compose up -d
   ```

### Debug Commands
```bash
# Check Docker daemon status
sudo systemctl status docker

# Check AWS CLI configuration
aws sts get-caller-identity
aws config list

# Check available disk space
df -h

# Check memory usage
free -h
```

## 🔄 Updates and Maintenance

### Regular Updates
```bash
# Update deployment script
wget -O simple-deploy.sh https://raw.githubusercontent.com/your-repo/deploy-apps/simple-deploy.sh

# Update application
./simple-deploy.sh
```

### Backup and Recovery
```bash
# Backup docker-compose.yml
cp docker-compose.yml docker-compose.yml.backup

# Backup container data
docker cp caldev-calendar_php-backend_1:/var/www/html/data ./backup_data

# Restore from backup
docker compose down
cp docker-compose.yml.backup docker-compose.yml
docker compose up -d
```

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review Docker logs: `docker compose logs`
3. Verify AWS credentials and permissions
4. Check server resources (CPU, memory, disk space)

