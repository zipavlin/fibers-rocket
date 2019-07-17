module.exports = {
    base: '/fibers-rocket/',
    title: 'Fibers Rocket',
    description: 'Laravel development tool for rapid app development with smart model, migration, controller and views scaffolding.',
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
                    { text: 'Ignite', link: '/commands/ignite' },
                    { text: 'Create', link: '/commands/create' },
                    { text: 'Model', link: '/commands/model' },
                    { text: 'Controller', link: '/commands/controller' },
                    { text: 'Layout', link: '/commands/layout' },
                    { text: 'Route', link: '/commands/route' },
                    { text: 'Migration', link: '/commands/migration' },
                    { text: 'Guard', link: '/commands/guard' },
                    { text: 'Language', link: '/commands/language' },
                    { text: 'Pivot', link: '/commands/pivot' },
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
                    '/commands/ignite',
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
