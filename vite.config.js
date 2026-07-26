import { defineConfig } from "vite-plus";
import laravel from "laravel-vite-plugin";
import { bunny } from "laravel-vite-plugin/fonts";
import tailwindcss from "@tailwindcss/vite";
import { lazyPlugins } from "vite-plus";

export default defineConfig({
    staged: {
        "*": "vp check --fix",
    },
    fmt: {},
    lint: {
        jsPlugins: [{ name: "vite-plus", specifier: "vite-plus/oxlint-plugin" }],
        rules: { "vite-plus/prefer-vite-plus-imports": "error" },
        options: { typeAware: true, typeCheck: true },
    },
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/filament/admin/theme.css",
                "resources/css/filament/chinook/theme.css",
                "resources/css/filament/northwind/theme.css",
                "resources/css/filament/pagila/theme.css",
                "resources/js/app.js",
                "resources/js/passkeys.js",
            ],
            refresh: true,
            fonts: [
                bunny("Instrument Sans", {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
