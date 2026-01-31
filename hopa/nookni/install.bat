@echo off
echo 🚀 Installing Nookni - Modern Laravel Admin Dashboard with Tailwind CSS
echo ==================================================================

echo ✅ Checking prerequisites...

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Composer is not installed. Please install Composer first.
    pause
    exit /b 1
)

where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ npm is not installed. Please install Node.js and npm first.
    pause
    exit /b 1
)

echo ✅ Prerequisites check passed

echo 📦 Installing PHP dependencies...
composer install

echo 📦 Installing Node.js dependencies...
npm install

if not exist .env (
    echo ⚙️ Creating environment file...
    copy .env.example .env
    php artisan key:generate
) else (
    echo ⚙️ Environment file already exists
)

echo 🔨 Building assets...
npm run dev

echo.
echo 🎉 Installation completed successfully!
echo.
echo Next steps:
echo 1. Configure your database in .env file
echo 2. Run migrations: php artisan migrate
echo 3. Seed database: php artisan db:seed
echo 4. Start development server: php artisan serve
echo.
echo 📚 For more information, see README.md
pause