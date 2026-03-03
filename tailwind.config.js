/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./*.php",       // Scans index.php, habitants.php, etc.
    "./**/*.php",    // Scans subfolders like includes/ or pages/
    // Add more patterns if needed, e.g. "./components/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'hoa-green': '#166534',    // Dark green example – adjust to match subdivision branding
        'hoa-light': '#dcfce7',    // Lighter variant
        'hoa-accent': '#22c55e',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],  // Modern default; download Inter if you want
      },
    },
  },
  plugins: [],
}