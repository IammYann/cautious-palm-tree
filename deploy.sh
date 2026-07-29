#!/usr/bin/env bash

# Exit on error
set -e

echo "starting Deployment Optimization Routine"

# Optimize Laravel configurations and routes
echo "Caching configuration"
php artisan config:cache

echo "Caching route"
php artisan route:cache

echo "Caching view"
php artisan view:cache

echo "Caching event"
php artisan event:cache

echo "Application performance caching comple"
