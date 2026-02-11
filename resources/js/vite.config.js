import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/universal.css',
                'resources/css/page_parts/navbar.css',
                'resources/css/page_parts/footer.css',
                // Add your new password page CSS files here:
                'resources/css/customer/password_page/reset_password.css',
                'resources/css/customer/password_page/enter_code.css',
                'resources/css/customer/password_page/new_password.css',
            ],
            refresh: true,
        }),
    ],
});