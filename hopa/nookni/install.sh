#!/bin/bash

echo "🚀 Installing Nookni - Modern Laravel Admin Dashboard with Tailwind CSS"
echo "=================================================================="

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install Node.js and npm first."
    exit 1
fi

echo "✅ Prerequisites check passed"

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm install

# Create environment file
if [ ! -f .env ]; then
    echo "⚙️ Creating environment file..."
    cp .env.example .env
    php artisan key:generate
else
    echo "⚙️ Environment file already exists"
fi

# Build assets
echo "🔨 Building assets..."
npm run dev

echo ""
echo "🎉 Installation completed successfully!"
echo ""
echo "Next steps:"
echo "1. Configure your database in .env file"
echo "2. Run migrations: php artisan migrate"
echo "3. Seed database: php artisan db:seed"
echo "4. Start development server: php artisan serve"
echo ""
echo "📚 For more information, see README.md"