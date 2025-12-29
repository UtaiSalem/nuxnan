export default {
  content: [
    './components/**/*.{js,vue,ts}',
    './PlearndComponents/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Vikinger-inspired Sci-Fi/Gamification Colors
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
        secondary: {
          50: '#faf5ff',
          100: '#f3e8ff',
          200: '#e9d5ff',
          300: '#d8b4fe',
          400: '#c084fc',
          500: '#a855f7',
          600: '#9333ea',
          700: '#7e22ce',
          800: '#6b21a8',
          900: '#581c87',
        },
        // Vikinger Theme Colors
        'vikinger-cyan': '#23d2e2',
        'vikinger-purple': '#615dfa',
        'vikinger-pink': '#ff6b81',
        'vikinger-green': '#00e676',
        'vikinger-orange': '#ff9500',
        'vikinger-yellow': '#ffd700',
        // Dark Theme
        'vikinger-dark': {
          DEFAULT: '#1d2333',
          50: '#3a4254',
          100: '#2f3749',
          200: '#282f3f',
          300: '#232a39',
          400: '#1d2333',
          500: '#181d2a',
          600: '#131721',
          700: '#0e1118',
          800: '#090b0f',
          900: '#040506',
        },
        // Light Theme
        'vikinger-light': {
          DEFAULT: '#f8f8fb',
          50: '#ffffff',
          100: '#fdfdfe',
          200: '#f8f8fb',
          300: '#f0f0f5',
          400: '#e8e8ee',
          500: '#d4d4dc',
          600: '#adaeb6',
          700: '#8f8f96',
          800: '#6c6c72',
          900: '#45454a',
        },
        // Accent gradients
        accent: {
          cyan: '#23d2e2',
          purple: '#615dfa',
          pink: '#ff6b81',
          gradient: 'linear-gradient(90deg, #615dfa 0%, #23d2e2 100%)',
        },
      },
      fontFamily: {
        sans: ['Prompt', 'Inter', 'sans-serif'],
        heading: ['Outfit', 'sans-serif'],
        prompt: ['Prompt', 'sans-serif'],
      },
      backgroundImage: {
        // Sci-Fi Gradients
        'gradient-vikinger': 'linear-gradient(90deg, #615dfa 0%, #23d2e2 100%)',
        'gradient-pink': 'linear-gradient(90deg, #ff6b81 0%, #615dfa 100%)',
        'gradient-green': 'linear-gradient(90deg, #00e676 0%, #23d2e2 100%)',
        'gradient-dark': 'linear-gradient(180deg, #1d2333 0%, #0e1118 100%)',
        'gradient-card': 'linear-gradient(145deg, rgba(97, 93, 250, 0.1) 0%, rgba(35, 210, 226, 0.1) 100%)',
        // Glow effects
        'glow-cyan': 'radial-gradient(circle, rgba(35, 210, 226, 0.3) 0%, transparent 70%)',
        'glow-purple': 'radial-gradient(circle, rgba(97, 93, 250, 0.3) 0%, transparent 70%)',
      },
      boxShadow: {
        'vikinger': '0 4px 20px rgba(97, 93, 250, 0.25)',
        'vikinger-lg': '0 8px 40px rgba(97, 93, 250, 0.35)',
        'cyan-glow': '0 0 20px rgba(35, 210, 226, 0.5)',
        'purple-glow': '0 0 20px rgba(97, 93, 250, 0.5)',
        'card': '0 4px 16px rgba(0, 0, 0, 0.1)',
        'card-dark': '0 4px 16px rgba(0, 0, 0, 0.4)',
      },
      borderRadius: {
        'vikinger': '12px',
        'vikinger-lg': '16px',
        'vikinger-xl': '24px',
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'slide-down': 'slideDown 0.3s ease-out',
        'slide-in-right': 'slideInRight 0.3s ease-out',
        'slide-in-left': 'slideInLeft 0.3s ease-out',
        'scale-up': 'scaleUp 0.2s ease-out',
        'bounce-in': 'bounceIn 0.5s ease-out',
        'loader': 'loader 1.5s infinite ease-in-out',
        'pulse-glow': 'pulseGlow 2s infinite',
        'float': 'float 3s ease-in-out infinite',
        'shimmer': 'shimmer 2s linear infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideDown: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideInRight: {
          '0%': { transform: 'translateX(100%)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        slideInLeft: {
          '0%': { transform: 'translateX(-100%)', opacity: '0' },
          '100%': { transform: 'translateX(0)', opacity: '1' },
        },
        scaleUp: {
          '0%': { transform: 'scale(0.9)', opacity: '0' },
          '100%': { transform: 'scale(1)', opacity: '1' },
        },
        bounceIn: {
          '0%': { transform: 'scale(0.3)', opacity: '0' },
          '50%': { transform: 'scale(1.05)' },
          '70%': { transform: 'scale(0.9)' },
          '100%': { transform: 'scale(1)', opacity: '1' },
        },
        loader: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        pulseGlow: {
          '0%, 100%': { boxShadow: '0 0 5px rgba(35, 210, 226, 0.5)' },
          '50%': { boxShadow: '0 0 20px rgba(35, 210, 226, 0.8)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
      },
    },
  },
  plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
}
