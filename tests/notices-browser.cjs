const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('node:assert/strict');
(async () => {
    const browser = await chromium.launch({ headless: true, channel: process.env.APPARTMENT_BROWSER_CHANNEL || 'chrome' });
    try {
        const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent(`<html><body><a id="background" href="#">Background</a><div id="wpbody-content"><div class="wrap"><h1>Dashboard</h1><div id="original" class="notice notice-warning"><p>Warning example</p><button id="action">Original action</button></div><form><div id="validation" class="notice notice-error">Form error</div></form><div id="inline" class="notice inline">Inline status</div></div></div><div id="appartment-notice-overlay" hidden></div><section id="appartment-notice-panel" role="dialog" hidden><header><h2>Notifiche <span class="appartment-notice-count">0</span></h2><button id="appartment-notice-close">Chiudi</button></header><button id="appartment-notice-toggle">Toggle</button><p id="appartment-notice-empty">Empty</p><div id="appartment-notice-live"></div><p id="appartment-notice-save-status"></p><a href="#">Archive</a></section><button id="appartment-notice-launcher">Notifiche <span class="appartment-notice-count">0</span></button></body></html>`);
        await page.addStyleTag({ path: 'appartment-dashboard/assets/notices.css' });
        await page.evaluate(() => {
            window.appartmentNotices = { storage: 'test', source: '/wp-admin/index.php', screen: 'Bacheca', nonce: 'test', ajax: '/ajax', context: 'site' };
            window.saved = [];
            window.fetch = async (url, options) => { saved.push(JSON.parse(options.body.get('items'))); return { ok: true, json: async () => ({ success: true }) }; };
            document.getElementById('action').addEventListener('click', () => { window.actionClicked = true; });
        });
        await page.addScriptTag({ path: 'appartment-dashboard/assets/notices.js' });
        await page.waitForTimeout(400);
        assert.equal(await page.locator('#original').isVisible(), false);
        assert.equal(await page.locator('#validation').isVisible(), true);
        assert.equal(await page.locator('#inline').isVisible(), true);
        assert.equal(await page.locator('#appartment-notice-launcher .appartment-notice-count').innerText(), '1');
        await page.click('#appartment-notice-launcher');
        assert.equal(await page.locator('#original').isVisible(), true);
        await page.click('#action');
        assert.equal(await page.evaluate(() => window.actionClicked), true);
        await page.click('#appartment-notice-close');
        assert.equal(await page.locator('.wrap > #original').count(), 1);
        assert.equal(await page.locator('#original').isVisible(), false);
        await page.evaluate(() => { const node = document.createElement('div'); node.id = 'late'; node.className = 'notice notice-error'; node.innerHTML = '<p>Late error</p>'; document.querySelector('.wrap').append(node); });
        await page.waitForTimeout(700);
        assert.equal(await page.locator('#late').isVisible(), false);
        assert.equal(await page.locator('#appartment-notice-launcher .appartment-notice-count').innerText(), '2');
        assert.equal(await page.evaluate(() => saved.flat().some(row => row.text === 'Late error' && row.type === 'error')), true);
        await page.click('#appartment-notice-launcher');
        await page.click('#appartment-notice-toggle');
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('#original').isVisible(), true);
        await page.click('#appartment-notice-launcher');
        await page.evaluate(() => document.getElementById('late').remove());
        await page.waitForTimeout(500);
        assert.equal(await page.locator('#appartment-notice-launcher .appartment-notice-count').innerText(), '1');
        // Exercise archive search and severity together without collecting archive cards.
        await page.keyboard.press('Escape');
        await page.evaluate(() => {
            const archive = document.createElement('div'); archive.className = 'appartment-notice-archive';
            archive.innerHTML = '<input id="appartment-notice-search"><select id="appartment-notice-filter"><option value="">All</option><option value="error">Error</option></select><p id="appartment-archive-empty" hidden>Empty</p><article class="appartment-archive-item" data-type="error">Payment error</article><article class="appartment-archive-item" data-type="info">General information</article>';
            document.getElementById('wpbody-content').append(archive);
        });
        // Archive handlers are bound at initial load, so test it in a fresh document.
        const archivePage = await browser.newPage();
        await archivePage.setContent('<div id="wpbody-content"><div class="appartment-notice-archive"><input id="appartment-notice-search"><select id="appartment-notice-filter"><option value="">All</option><option value="error">Error</option></select><p id="appartment-archive-empty" hidden>Empty</p><article class="appartment-archive-item" data-type="error">Payment error</article><article class="appartment-archive-item" data-type="info">General information</article></div></div>' + await page.locator('#appartment-notice-panel').evaluate(n => n.outerHTML) + '<div id="appartment-notice-overlay" hidden></div><button id="appartment-notice-launcher">Open</button>');
        await archivePage.evaluate(() => { window.appartmentNotices = {storage: 'archive', ajax: '/ajax'}; });
        await archivePage.addScriptTag({ path: 'appartment-dashboard/assets/notices.js' });
        await archivePage.selectOption('#appartment-notice-filter', 'error');
        assert.equal(await archivePage.locator('article:visible').count(), 1);
        await archivePage.fill('#appartment-notice-search', 'unmatched');
        assert.equal(await archivePage.locator('article:visible').count(), 0);
        assert.equal(await archivePage.locator('#appartment-archive-empty').isVisible(), true);
        await archivePage.close();
        await page.setViewportSize({width: 390, height: 844});
        await page.click('#appartment-notice-launcher');
        assert.equal(await page.locator('#appartment-notice-close').isVisible(), true);
        await page.screenshot({ path: '/tmp/appartment-notice-center-mobile.png' });
        await page.setViewportSize({width: 1280, height: 900});
        await page.screenshot({ path: '/tmp/appartment-notice-center.png' });
        assert.deepEqual(errors, []);
        console.log('PASS browser: hide, live panel, preserved actions, restore, dynamic notices, archive, toggle, Escape, dismissal');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
