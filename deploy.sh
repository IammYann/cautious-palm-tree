#!/usr/bin/env bash

# Exit on error
set -e

echo "🚀 Starting Deployment Optimization Routine..."

# Optimize Laravel configurations and routes
echo "📦 Caching configuration..."
php artisan config:cache

echo "🛣️ Caching routes..."
php artisan route:cache

echo "🎨 Caching views..."
php artisan view:cache

echo "📡 Caching events..."
php artisan event:cache

echo "✅ Application performance caching complete!"
