# Nookni - Modern Laravel Admin Dashboard with Tailwind CSS

Nookni is a modern, responsive Laravel admin dashboard that uses Tailwind CSS for styling. This project is a modernized version of the original Hope UI Bootstrap admin template, converted to use Tailwind CSS for better performance and maintainability.

## Features

- **Modern Technology Stack**: Built with Laravel 11 and Tailwind CSS 3.4
- **Responsive Design**: Fully responsive layout that works on all devices
- **Component-Based**: Modular component architecture for easy maintenance
- **Dark Mode Support**: Built-in dark mode functionality
- **RTL Support**: Right-to-left language support
- **Rich UI Components**: Comprehensive set of UI components
- **Data Tables**: Advanced data tables with sorting and filtering
- **Charts & Graphs**: Interactive charts using ApexCharts
- **Forms**: Various form layouts and validation
- **Authentication**: Complete authentication system
- **Role Management**: User roles and permissions system

## Technology Stack

- **Backend**: Laravel 11
- **Frontend**: Tailwind CSS 3.4
- **Build Tool**: Laravel Mix
- **Charts**: ApexCharts
- **Icons**: Iconly
- **Data Tables**: DataTables.net
- **Date Picker**: Vanilla JS Datepicker

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL or PostgreSQL database

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd nookni
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   - Edit the `.env` file with your database credentials
   - Run the migrations:
   ```bash
   php artisan migrate
   ```

6. **Seed the database**
   ```bash
   php artisan db:seed
   ```

7. **Build assets**
   ```bash
   npm run dev
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

## Build Commands

- `npm run dev` - Compile assets for development
- `npm run watch` - Watch for changes and recompile
- `npm run production` - Compile and minify assets for production

## Project Structure

```
nookni/
├── app/                    # Laravel application code
├── bootstrap/              # Bootstrap files
├── config/                # Configuration files
├── database/              # Database files
├── public/                # Public assets
├── resources/             # View files and assets
│   ├── css/              # CSS files
│   ├── js/               # JavaScript files
│   ├── sass/             # SCSS files
│   └── views/            # Blade templates
├── routes/                # Route definitions
├── storage/               # Storage files
├── tests/                 # Test files
├── tailwind.config.js      # Tailwind CSS configuration
├── webpack.mix.js         # Laravel Mix configuration
└── postcss.config.js      # PostCSS configuration
```

## Tailwind CSS Configuration

The project uses a custom Tailwind CSS configuration that includes:

- **Custom Colors**: Hope UI color palette
- **Custom Components**: Pre-built components matching the original design
- **Responsive Utilities**: Enhanced responsive utilities
- **Animations**: Custom animations for better UX

### Custom Colors

- `primary`: #3a57e8 (Hope UI primary blue)
- `info`: #4bc7d2 (Hope UI info cyan)
- `success`: #17904b (Hope UI success green)
- `warning`: #f6c23e (Hope UI warning yellow)
- `danger`: #e74a3b (Hope UI danger red)
- `secondary`: #8A92A6 (Hope UI secondary gray)

## Components

The project includes a comprehensive set of Tailwind CSS components:

### Cards
- Basic cards with headers and bodies
- Progress widgets
- Credit card widgets
- Statistics cards

### Forms
- Form controls with validation states
- Custom checkboxes and radios
- Date pickers
- File uploads

### Navigation
- Responsive navbar
- Sidebar navigation
- Breadcrumbs
- Pagination

### Tables
- Data tables with sorting
- Responsive tables
- Tables with actions

### Charts
- Line charts
- Bar charts
- Pie charts
- Progress indicators

## Customization

### Adding New Colors

1. Update `tailwind.config.js`:
```javascript
theme: {
  extend: {
    colors: {
      'new-color': '#your-color',
    }
  }
}
```

2. Use in your components:
```html
<div class="bg-new-color text-white">
  Content
</div>
```

### Adding New Components

1. Add to `resources/css/app.css`:
```css
@layer components {
  .new-component {
    @apply flex items-center justify-center p-4 bg-primary text-white rounded-lg;
  }
}
```

2. Use in your views:
```html
<div class="new-component">
  Content
</div>
```

## Deployment

### Production Build

1. **Optimize assets**
   ```bash
   npm run production
   ```

2. **Clear caches**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Set environment variables**
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please open an issue on the GitHub repository.

---

**Note**: This project maintains the same visual appearance as the original Hope UI template while leveraging the modern capabilities of Tailwind CSS for better performance and developer experience.