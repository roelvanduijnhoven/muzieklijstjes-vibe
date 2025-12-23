#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# Configuration
REGISTRY_NAME="roelvd-registry" # REPLACE THIS with your actual registry name
IMAGE_NAME="register-vibe"
TAG="latest" # You can change this to use version numbers or git commit hashes if preferred
FULL_IMAGE_PATH="registry.digitalocean.com/$REGISTRY_NAME/$IMAGE_NAME:$TAG"

echo "🚀 Starting build process for $FULL_IMAGE_PATH..."

# 1. Login to DigitalOcean Registry
# Assumes 'doctl' is installed and authenticated
echo "🔑 Logging in to DigitalOcean Registry..."
if ! doctl registry login; then
    echo "❌ Failed to login to registry. Please check your doctl configuration."
    exit 1
fi

# 2. Build the Docker image
echo "🏗️  Building Docker image..."
# Using --platform linux/amd64 is important if building on Apple Silicon (M1/M2) for standard servers
docker build --platform linux/amd64 -t "$FULL_IMAGE_PATH" --target packaged .

# 3. Push the image
echo "⬆️  Pushing image to registry..."
docker push "$FULL_IMAGE_PATH"

echo "✅ Success! Image published: $FULL_IMAGE_PATH"
echo "   To deploy changes, run: kubectl rollout restart deployment/register-vibe"



