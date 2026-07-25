import daisyui from 'daisyui'

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        display: ['Syne', 'Manrope', 'sans-serif'],
        sans: ['Manrope', 'Segoe UI', 'sans-serif'],
      },
      colors: {
        ink: '#0c1b24',
        soft: '#3d5160',
        paper: '#eef3f6',
        accent: '#0f766e',
      },
    },
  },
  plugins: [daisyui],
  daisyui: {
    themes: [
      {
        shortadmin: {
          primary: '#0f766e',
          secondary: '#0c1b24',
          accent: '#14b8a6',
          neutral: '#0c1b24',
          'base-100': '#eef3f6',
          'base-200': '#e2e9ee',
          'base-300': '#cfd9e1',
          'base-content': '#0c1b24',
          'neutral-content': '#eef3f6',
          info: '#0284c7',
          success: '#0f766e',
          warning: '#b45309',
          error: '#be123c',
        },
      },
    ],
  },
}
