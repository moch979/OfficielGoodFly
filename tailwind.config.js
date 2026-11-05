/** Build: tools\tailwindcss.exe -c tailwind.config.js -i assets/css/input.css -o assets/css/site.css --minify */
module.exports = {
    content: ['./index.php', './includes/design.php'],
    theme: {
        extend: {
            colors: {
                brand: {
                    green: 'rgb(var(--gf-green) / <alpha-value>)',
                    darkgreen: 'rgb(var(--gf-darkgreen) / <alpha-value>)',
                    gold: 'rgb(var(--gf-gold) / <alpha-value>)',
                    lightgold: 'rgb(var(--gf-lightgold) / <alpha-value>)',
                    ivory: 'rgb(var(--gf-ivory) / <alpha-value>)',
                    lightivory: 'rgb(var(--gf-lightivory) / <alpha-value>)',
                    slate: 'rgb(var(--gf-slate) / <alpha-value>)',
                },
            },
            boxShadow: {
                'gold-soft': '0 10px 30px -10px rgba(216, 195, 155, 0.35)',
                lux: '0 20px 40px -15px rgba(24, 59, 53, 0.12)',
            },
        },
    },
    plugins: [],
};
