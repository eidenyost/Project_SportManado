const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const path = require('node:path');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE_PATH || 'playwright');

const base = process.env.SPORTMANADO_TEST_URL;
if (!base || !['127.0.0.1','localhost'].includes(new URL(base).hostname)) throw Error('Use an isolated localhost fixture server via SPORTMANADO_TEST_URL.');
const output = process.env.SPORTMANADO_EVIDENCE_DIR || path.resolve('browser-evidence');
const password = process.env.SPORTMANADO_TEST_PASSWORD;
if (!password) throw Error('SPORTMANADO_TEST_PASSWORD is required for test accounts.');

const suites = [
    ['guest', null, ['/', '/login','/register','/register/form?role=user','/register/form?role=organizer','/forgot-password']],
    ['participant','participant.with.a.long.address@example.test',['/dashboard','/events','/events/1','/events/2/register','/events/3/register','/tickets','/tickets/1','/profile','/event-registration','/event-registration/status','/panduan','/galeri']],
    ['organizer','organizer@example.test',['/organizer/dashboard','/organizer/events','/organizer/events/create','/organizer/events/1/edit','/organizer/events/1/participants','/organizer/events/1/scan','/organizer/finances/3','/organizer/finances/3/withdraw','/organizer/notifications']],
    ['admin','admin@example.test',['/admin/dashboard','/admin/dashboard?tab=all_events','/admin/dashboard?tab=organizer_verifications','/admin/dashboard?tab=pending_validation','/admin/dashboard?tab=manage_users','/admin/dashboard?tab=gallery','/admin/dashboard?tab=payment_monitoring','/admin/dashboard?tab=withdrawal_requests']],
];

(async () => {
    await fs.mkdir(output, { recursive: true });
    const browser = await chromium.launch({ headless: true });
    const results = [], errors = [], failures = [];
    try {
        for (const [role,email,urls] of suites) {
            const context = await browser.newContext();
            const page = await context.newPage();
            page.on('pageerror', e => errors.push({role,url:page.url(),message:e.message}));
            // Core UI must work without any third-party frontend connection.
            await context.route('**/*', route => new URL(route.request().url()).origin === new URL(base).origin || route.request().url().startsWith('data:') ? route.continue() : route.abort());
            if (email) {
                await page.goto(base+'/login');
                await page.locator('[name=email]').fill(email);
                await page.locator('[name=password]').fill(password);
                await page.locator('button[type=submit]').click();
                await page.waitForURL(/dashboard/);
            }
            for (const width of [320,360,390,430,768,1024,1440]) {
                await page.setViewportSize({width,height:800});
                for (const url of urls) {
                    const response = await page.goto(base+url, {waitUntil:'load'});
                    await page.evaluate(()=>document.fonts.ready);
                    const measurement = await page.evaluate(() => ({
                        width:innerWidth,scroll:document.documentElement.scrollWidth,
                        overflow:[...document.querySelectorAll('main *')].filter(element=> {
                            const rect=element.getBoundingClientRect();
                            return rect.width && rect.right>innerWidth+2 && !element.closest('.overflow-x-auto');
                        }).slice(0,5).map(element=>({tag:element.tagName,classes:element.getAttribute('class'),right:Math.round(element.getBoundingClientRect().right)})),
                    }));
                    const result={role,url,status:response.status(),...measurement};
                    results.push(result);
                    if(result.status>=400 || result.scroll>width+1 || result.overflow.length) failures.push(result);
                    if ([360,1440].includes(width) && (url.endsWith('/dashboard') || url==='/tickets/1' || url.endsWith('/scan'))) {
                        await page.screenshot({path:path.join(output,`${role}-${url.replace(/[^a-z0-9]/gi,'-')}-${width}.png`),fullPage:true});
                    }
                }
                if (role==='organizer' && width<768) {
                    await page.locator('#sidebar-toggle').click();
                    assert.equal(await page.locator('#sidebar-toggle').getAttribute('aria-expanded'),'true');
                    await page.keyboard.press('Escape');
                    assert.equal(await page.locator('#sidebar-toggle').getAttribute('aria-expanded'),'false');
                }
            }
            await context.close();
        }
        await fs.writeFile(path.join(output,'responsive-results.json'),JSON.stringify({results,failures,errors},null,2));
        console.log(JSON.stringify({checks:results.length,failures,errors},null,2));
        assert.equal(failures.length,0,'Responsive checks failed');
        assert.equal(errors.length,0,'Browser JavaScript errors found');
    } finally { await browser.close(); }
})().catch(error=>{ console.error(error.message); process.exitCode=1; });
