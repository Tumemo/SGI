const { defineConfig } = require('@playwright/test');

const chromePath = process.env.SGI_CHROME_PATH;
const launchOptions = chromePath ? { executablePath: chromePath } : {};

module.exports = defineConfig({
    testDir: __dirname,
    globalSetup: require.resolve('./global-setup.cjs'),
    timeout: 180_000,
    expect: { timeout: 20_000 },
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    outputDir: 'test-results',
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }]
    ],
    use: {
        baseURL: process.env.SGI_BASE_URL || 'http://localhost/SGI/',
        viewport: { width: 1440, height: 900 },
        headless: process.env.SGI_HEADFUL !== '1',
        launchOptions,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure'
    }
});
