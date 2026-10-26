// Regenerates public/images/templates/<key>.jpg from the public gallery previews.
// Usage: run `php artisan serve --port=8127`, then: node scripts/template-thumbnails.cjs [base-url]
const { chromium } = require('playwright');

(async () => {
  const base = process.argv[2] || 'http://127.0.0.1:8127';
  const browser = await chromium.launch();
  // 56px taller than the crop: the "Use this template" bar sits above it.
  const page = await (await browser.newContext({ viewport: { width: 1280, height: 856 }, reducedMotion: 'reduce' })).newPage();
  for (const key of ['gun-lap', 'stadium-split', 'equipment-close', 'night-circuit', 'heritage-crest']) {
    await page.goto(`${base}/templates/${key}`, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.querySelectorAll('[data-reveal]').forEach(e => e.classList.add('is-visible')));
    await page.waitForTimeout(400);
    await page.screenshot({ path: `public/images/templates/${key}.jpg`, type: 'jpeg', quality: 72, clip: { x: 0, y: 56, width: 1280, height: 800 } });
    console.log(`public/images/templates/${key}.jpg`);
  }
  await browser.close();
})();
