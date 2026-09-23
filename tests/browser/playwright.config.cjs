const { defineConfig } = require('@playwright/test');

const chromePath = process.env.SGI_CHROME_PATH;
const launchOptions = chromePath ? { executablePath: chromePath } : {};
const browserOutputDir = process.env.SGI_BROWSER_OUTPUT_DIR || 'test-results';
const browserReportDir = process.env.SGI_BROWSER_REPORT_DIR || 'playwright-report';

module.exports = defineConfig({
    testDir: __dirname,
    globalSetup: require.resolve('./global-setup.cjs'),
    timeout: 180_000,
    expect: {
        timeout: 20_000,
        toHaveScreenshot: {
            pathTemplate: '{testDir}/{testFileName}-snapshots/{arg}-{platform}{ext}',
        },
    },
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    outputDir: browserOutputDir,
    reporter: [
        ['list'],
        ['html', { outputFolder: browserReportDir, open: 'never' }]
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
