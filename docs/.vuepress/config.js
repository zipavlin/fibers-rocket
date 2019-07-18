module.exports = {
    base: '/fibers-rocket/',
    title: 'Fibers Rocket',
    description: 'Laravel development tool for rapid app development with smart model, migration, controller and views scaffolding.',
    head: [
        ['link', { rel: "apple-touch-icon", sizes: "180x180", href: "/apple-touch-icon.png"}],
        ['link', { rel: "icon", type: "image/png", sizes: "32x32", href: "/favicon-32x32.png"}],
        ['link', { rel: "icon", type: "image/png", sizes: "16x16", href: "/favicon-16x16.png"}],
        ['link', { rel: "manifest", href: "/site.webmanifest"}],
        ['link', { rel: "shortcut icon", href: "/favicon.ico"}],
    ],
    theme: '@vuepress/theme-default',
    plugins: [
        '@vuepress/search', { searchMaxSuggestions: 10 },
        '@vuepress/active-header-links',
        '@vuepress/last-updated',
        '@vuepress/nprogress',
        'vuepress-plugin-reading-time',
    ],
    themeConfig: {
        logo: '/logo-rocket-inline.svg',
        nav: [
            { text: 'Home', link: '/' },
            { text: 'Guide', link: '/guide' },
            {
                text: 'Commands',
                items: [
                    { text: 'Setup App', link: '/commands/app' },
                    { text: 'Create MVC', link: '/commands/create' },
                    { text: 'Make Model', link: '/commands/model' },
                    { text: 'Make Controller', link: '/commands/controller' },
                    { text: 'Make Layout', link: '/commands/layout' },
                    { text: 'Make Route', link: '/commands/route' },
                    { text: 'Make Migration', link: '/commands/migration' },
                    { text: 'Make Guard', link: '/commands/guard' },
                    { text: 'Make Language', link: '/commands/language' },
                    { text: 'Make Pivot', link: '/commands/pivot' },
                ]
            },
            { text: 'Attribute Input', link: '/attributes' },
        ],
        sidebar: [
            '/',
            ['/guide', 'Guide'],
            {
                title: 'Commands',
                children: [
                    '/commands/app',
                    '/commands/create',
                    '/commands/model',
                    '/commands/controller',
                    '/commands/layout',
                    '/commands/route',
                    '/commands/migration',
                    '/commands/guard',
                    '/commands/language',
                    '/commands/pivot',
                ]
            },
            ['/attributes', 'Attribute Input'],
        ],
        displayAllHeaders: true,
        lastUpdated: 'Last Updated',
        repo: 'zipavlin/fibers-rocket',
        editLinks: true,
        editLinkText: 'Help us improve this page!',
        evergreen: true
    }
};
