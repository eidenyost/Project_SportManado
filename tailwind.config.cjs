module.exports = {
    content: ['./resources/views/**/*.blade.php', './resources/js/**/*.js'],
    darkMode: 'class',
    safelist: [{ pattern: /^(bg|text|border)-(emerald|red|blue|amber)-(300|400|500|800|950)(\/\d+)?$/ }],
    theme: {
        extend: {
            colors: {
                dark: { bg: '#080c14', card: '#0d1527', border: '#1e293b' },
                'dark-bg': '#080c14', 'dark-card': '#0d1527', 'dark-border': '#1e293b',
                accent: { DEFAULT: '#ff6b00', hover: '#e05e00', light: 'rgba(255,107,0,0.1)' },
                success: { DEFAULT: '#34d399', bg: 'rgba(5,150,105,0.1)', border: 'rgba(5,150,105,0.3)' },
                error: '#ef4444',
            },
            fontFamily: { sans: ['Inter', 'sans-serif'], body: ['Inter', 'sans-serif'], display: ['Archivo', 'sans-serif'] },
            fontSize: {
                'display-lg': ['48px', { lineHeight: '56px', letterSpacing: '-0.02em', fontWeight: '800' }],
                'headline-lg': ['32px', { lineHeight: '40px', letterSpacing: '-0.01em', fontWeight: '700' }],
                'headline-md': ['24px', { lineHeight: '32px', fontWeight: '600' }],
                'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'label-md': ['14px', { lineHeight: '20px', letterSpacing: '0.05em', fontWeight: '600' }],
                'label-sm': ['12px', { lineHeight: '16px', fontWeight: '500' }],
            },
            animation: { 'fade-in': 'fadeIn .4s ease backwards' },
            keyframes: { fadeIn: { '0%': { opacity: 0 }, '100%': { opacity: 1 } } },
        },
    },
    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/container-queries')],
};
