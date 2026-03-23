import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/universal_customer.css',
                'resources/css/universal_owner.css',
                'resources/css/page_parts/navbar.css',
                'resources/css/page_parts/footer.css',
                'resources/css/customer/signup.css',
                'resources/css/customer/login.css',
                'resources/css/customer/account.css',
                'resources/css/customer/edit_profile.css',
                'resources/css/customer/change_password.css',
                'resources/css/customer/reset_password.css',
                'resources/css/customer/enter_code.css',
                'resources/css/customer/new_password.css',
                'resources/css/customer/popups/tnc.css',
                'resources/css/customer/pages/aboutus.css',
                'resources/css/customer/pages/faqs.css',
                'resources/css/customer/pages/products.css',
                'resources/css/customer/pages/home.css',
                'resources/css/customer/pages/home_images.css',
                'resources/css/owner/parts/sidenav.css',
                'resources/css/owner/pages/content_management/content_management.css',
                'resources/css/owner/pages/inventory.css',
                'resources/css/owner/pages/orders.css',
                'resources/js/app.js',
                'resources/js/tnc.js',
                'resources/js/customeraccount_options.js',
                'resources/js/owner/sidebar_account.js',
                'resources/js/change_password_validation.js',
                'resources/js/edit_profile_validation.js',
                'resources/js/signup_validation.js',
                'resources/js/customer/pages/faqs.js',
                'resources/js/customer/pages/products_page.js',
                'resources/js/owner/content_page/main_content_page.js',
                'resources/js/owner/content_page/products_page_content_refactored.js',
                'resources/js/owner/content_page/edit_home_images_modal.js',
                'resources/js/owner/content_page/product_sample_modal.js',
                'resources/css/owner/pages/dashboard.css',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/storage': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
    },
});
