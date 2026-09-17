import { defineConfig } from 'vitepress'

const repository = 'https://github.com/crenspire/yii3-inertia'

export default defineConfig({
  title: 'Yii3 Inertia',
  description: 'Inertia.js server-side adapter for Yii3 and PSR-15 applications.',
  lang: 'en-US',
  base: '/yii3-inertia/',
  cleanUrls: true,
  lastUpdated: true,
  sitemap: {
    hostname: 'https://crenspire.github.io/yii3-inertia/',
  },
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/yii3-inertia/logo.svg' }],
    ['meta', { name: 'theme-color', content: '#6b4ce6' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Yii3 Inertia' }],
    ['meta', { property: 'og:description', content: 'Build modern single-page apps with Yii3, Inertia.js and React, Vue or Svelte.' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'Yii3 Inertia',

    nav: [
      { text: 'Guide', link: '/guide/introduction', activeMatch: '/guide/' },
      { text: 'Reference', link: '/reference/api', activeMatch: '/reference/' },
      { text: 'Examples', link: '/guide/examples' },
      {
        text: '2.x',
        items: [
          { text: 'Changelog', link: `${repository}/blob/develop/CHANGELOG.md` },
          { text: 'Upgrade from 1.x', link: '/guide/upgrade' },
          { text: 'Packagist', link: 'https://packagist.org/packages/crenspire/yii3-inertia' },
        ],
      },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting started',
          items: [
            { text: 'Introduction', link: '/guide/introduction' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Client-side setup', link: '/guide/client-setup' },
            { text: 'Without Yii3', link: '/guide/psr15' },
            { text: 'Configuration', link: '/guide/configuration' },
          ],
        },
        {
          text: 'The basics',
          items: [
            { text: 'Pages and responses', link: '/guide/responses' },
            { text: 'Redirects', link: '/guide/redirects' },
            { text: 'Shared data', link: '/guide/shared-data' },
            { text: 'Forms and validation', link: '/guide/forms' },
            { text: 'CSRF protection', link: '/guide/csrf' },
            { text: 'Flash data', link: '/guide/flash-data' },
            { text: 'Root view', link: '/guide/root-view' },
            { text: 'Vite', link: '/guide/vite' },
          ],
        },
        {
          text: 'Data and props',
          items: [
            { text: 'Props', link: '/guide/props' },
            { text: 'Partial reloads', link: '/guide/partial-reloads' },
            { text: 'Deferred props', link: '/guide/deferred-props' },
            { text: 'Merging props', link: '/guide/merging-props' },
            { text: 'Infinite scroll', link: '/guide/infinite-scroll' },
            { text: 'Once props', link: '/guide/once-props' },
          ],
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Asset versioning', link: '/guide/asset-versioning' },
            { text: 'History encryption', link: '/guide/history-encryption' },
            { text: 'Server-side rendering', link: '/guide/ssr' },
            { text: 'Long-running workers', link: '/guide/workers' },
            { text: 'Testing', link: '/guide/testing' },
          ],
        },
        {
          text: 'More',
          items: [
            { text: 'Examples', link: '/guide/examples' },
            { text: 'Upgrade from 1.x', link: '/guide/upgrade' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' },
          ],
        },
      ],
      '/reference/': [
        {
          text: 'Reference',
          items: [
            { text: 'API', link: '/reference/api' },
            { text: 'Configuration params', link: '/reference/params' },
            { text: 'Protocol support', link: '/reference/protocol' },
          ],
        },
      ],
    },

    socialLinks: [{ icon: 'github', link: repository }],

    editLink: {
      pattern: `${repository}/edit/develop/docs/:path`,
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    outline: {
      level: [2, 3],
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Crenspire',
    },
  },
})
