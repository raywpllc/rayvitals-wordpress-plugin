#!/bin/bash

# RayVitals WordPress Plugin - WP Engine Deployment Script
# This script builds and deploys the plugin to WP Engine

set -e

echo "🚀 RayVitals WordPress Plugin Deployment"
echo "======================================"

# Configuration
WPE_ENV="production"
WPE_INSTALL="your-wpengine-install-name" # Replace with your WP Engine install name

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if WP Engine install name is configured
if [ "$WPE_INSTALL" = "your-wpengine-install-name" ]; then
    echo -e "${RED}Error: Please configure your WP Engine install name in deploy.sh${NC}"
    exit 1
fi

# Check if we're in the right directory
if [ ! -f "rayvitals.php" ]; then
    echo -e "${RED}Error: Must run from plugin root directory${NC}"
    exit 1
fi

# Run pre-deployment checks
echo -e "${YELLOW}Running pre-deployment checks...${NC}"

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}Error: You have uncommitted changes. Please commit or stash them first.${NC}"
    exit 1
fi

# Get current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "Current branch: $CURRENT_BRANCH"

# Build assets if needed
if [ -f "package.json" ]; then
    echo -e "${YELLOW}Building assets...${NC}"
    npm install
    npm run build
fi

# Create deployment directory
DEPLOY_DIR=".deploy"
rm -rf $DEPLOY_DIR
mkdir -p $DEPLOY_DIR

# Copy plugin files
echo -e "${YELLOW}Preparing deployment files...${NC}"
rsync -av --exclude-from='.deployignore' ./ $DEPLOY_DIR/

# Create plugin version tag
VERSION=$(grep "Version:" rayvitals.php | sed 's/.*Version: //')
echo "Plugin version: $VERSION"

# Add WP Engine remote if not exists
if ! git remote | grep -q "wpengine"; then
    echo -e "${YELLOW}Adding WP Engine remote...${NC}"
    git remote add wpengine git@git.wpengine.com:${WPE_ENV}/${WPE_INSTALL}.git
fi

# Create temporary deployment branch
DEPLOY_BRANCH="deploy-$(date +%Y%m%d-%H%M%S)"
git checkout -b $DEPLOY_BRANCH

# Add deployment files
cp -R $DEPLOY_DIR/* ./
git add -A
git commit -m "Deploy version $VERSION to WP Engine"

# Push to WP Engine
echo -e "${YELLOW}Deploying to WP Engine...${NC}"
git push wpengine $DEPLOY_BRANCH:main --force

# Cleanup
echo -e "${YELLOW}Cleaning up...${NC}"
git checkout $CURRENT_BRANCH
git branch -D $DEPLOY_BRANCH
rm -rf $DEPLOY_DIR

echo -e "${GREEN}✅ Deployment complete!${NC}"
echo "Version $VERSION has been deployed to WP Engine ($WPE_INSTALL)"