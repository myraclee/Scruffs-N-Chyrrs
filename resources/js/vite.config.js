import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/universal.css",
                "resources/css/page_parts/navbar.css",
                "resources/css/page_parts/footer.css",

                "resources/css/customer/password_page/reset_password.css",
                "resources/css/customer/password_page/enter_code.css",
                "resources/css/customer/password_page/new_password.css",

                "resources/css/owner/inventory.css",
                "resources/js/owner/inventory.js",

                "resources/css/owner/pages/content_management/order_template.css",
                "resources/js/owner/content_page/order_template.js",
            ],
            refresh: true,
        }),
    ],
});
