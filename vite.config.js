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
                'resources/css/customer/aboutus.css',
                'resources/css/customer/popups/tnc.css',
                'resources/css/owner/parts/sidenav.css',
                'resources/css/owner/pages/content_management/contentmanagement.css',
                'resources/css/owner/pages/inventory.css',
                'resources/css/owner/pages/orders.css',
                'resources/js/app.js',
                'resources/js/tnc.js',
                'resources/js/customeraccount_options.js',
                'resources/js/owner/sidebar_account.js',
                'resources/js/change_password_validation.js',
                'resources/js/edit_profile_validation.js',
                'resources/js/signup_validation.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
