import {defineConfig} from 'vite'
import laravel from "laravel-vite-plugin";
import path from 'path'
import {wordpressPlugin, wordpressThemeJson} from "@roots/vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import dotenv from 'dotenv'

dotenv.config()

const appUrl = process.env.APP_URL?.replace(/\/$/, '') || 'http://localhost'

export default defineConfig({
    // base: '/assets/',
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                path.resolve(__dirname, 'src/js/main.js'),
                path.resolve(__dirname, 'src/css/main.css'),
                path.resolve(__dirname, 'src/css/editor.css'),
                path.resolve(__dirname, 'src/js/editor.js'),
            ],
            publicDirectory: path.resolve(__dirname, 'assets'),
            refresh: [{ paths: ['**/*.php'], config: { delay: 300 } }],
        }),

        wordpressThemeJson({
            cssFile: 'main.css',
            outputPath: 'theme.json',
            disableTailwindColors: false,
            disableTailwindFonts: false,
            disableTailwindFontSizes: false,
        }),
    ],
    resolve: {
        alias: {
            '@scripts': '/src/scripts/',
            '@styles': '/src/styles/',
            '@images': '/src/images/',
            '@fonts': '/src/fonts/'
        }
    },
    build: {
        assetsDir: '.'
    },
    server: {
        host: 'localhost',
        port: 5173,
        cors: {
            origin: appUrl,
        },
        hmr: {
            host: 'localhost',
        }
    }
})
