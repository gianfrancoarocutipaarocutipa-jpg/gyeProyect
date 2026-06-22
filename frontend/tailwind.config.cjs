/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eef',
          100: '#dce',
          200: '#bccee5',
          300: '#9caee0',
          400: '#7c8ed9',
          500: '#5d6dd0',
          600: '#4e5bbf',
          700: '#3e4ab0',
          800: '#2f3a87',
          900: '#1f285e'
        }
      }
    },
  },
  plugins: [],
}