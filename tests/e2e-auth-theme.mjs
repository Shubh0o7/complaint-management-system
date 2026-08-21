import { chromium } from 'playwright';

const baseUrl = process.env.E2E_BASE_URL || 'http://127.0.0.1:8765';
const browser = await chromium.launch({ headless: true });
const context = await browser.newContext();
const page = await context.newPage();
const checks = [];

function check(condition, message) {
  if (!condition) throw new Error(`E2E assertion failed: ${message}`);
  checks.push(message);
}

try {
  await page.goto(`${baseUrl}/index.html`, { waitUntil: 'networkidle' });
  await page.locator('#demoLogin').waitFor({ state: 'visible' });
  check(await page.locator('#demoLoginForm').isVisible(), 'login form is visible');
  check(await page.locator('#loginThemeToggle').isVisible(), 'login theme toggle is visible');

  await page.locator('#loginThemeToggle').click();
  check(await page.locator('body').evaluate((body) => body.classList.contains('dark-mode')), 'dark mode activates from login');
  check(await page.evaluate(() => localStorage.getItem('campusresolve-demo-theme-v1')) === 'dark', 'dark mode persists to localStorage');

  await page.locator('#demoEmail').fill('admin@campus.edu');
  await page.locator('#demoPassword').fill('Admin@1234');
  await page.locator('#demoLoginForm button[type="submit"]').click();
  await page.locator('.app-shell').waitFor({ state: 'visible' });
  check(await page.locator('#accountRole').textContent() === 'Administrator', 'administrator credentials open administrator workspace');
  check((await page.locator('#overviewView').textContent()).includes('INSTITUTION CONTROL CENTER'), 'administrator dashboard layout is rendered');
  check(await page.locator('#roleSelectLabel').textContent() === 'Administrator', 'authenticated role is displayed as locked');
  check(await page.locator('#roleSelect').count() === 0, 'post-login role selector is not available');

  await page.locator('#themeToggle').click();
  check(!(await page.locator('body').evaluate((body) => body.classList.contains('dark-mode'))), 'light mode restores from authenticated shell');
  check(await page.evaluate(() => localStorage.getItem('campusresolve-demo-theme-v1')) === 'light', 'light mode persists to localStorage');

  await page.locator('#accountToggle').click();
  await page.locator('[data-account-action="logout"]').click();
  await page.locator('#demoLogin').waitFor({ state: 'visible' });
  check(await page.locator('.app-shell').evaluate((el) => el.classList.contains('hidden')), 'logout returns to login screen');
  check(await page.evaluate(() => sessionStorage.getItem('campusresolve-demo-auth-v1')) === null, 'logout clears demo session storage');

  check(await page.locator('[data-demo-role]').count() === 0, 'login has no role selector or auto-fill controls');
  check(await page.locator('#signupPane').textContent().then((text) => text.includes('Students must create an account first')), 'student registration is required for Student Dashboard');
  const roleAccounts = [
    ['admin@campus.edu', 'Admin@1234', 'Administrator'],
    ['manager@campus.edu', 'Manager@1234', 'Department Manager'],
    ['officer@campus.edu', 'Officer@1234', 'Complaint Officer']
  ];
  for (const [email, password, label] of roleAccounts) {
    await page.locator('#demoEmail').fill(email);
    await page.locator('#demoPassword').fill(password);
    await page.locator('#demoLoginForm button[type="submit"]').click();
    await page.locator('.app-shell').waitFor({ state: 'visible' });
    check(await page.locator('#accountRole').textContent() === label, `${label} manual credentials open only the assigned dashboard`);
    check(await page.locator('#roleSelectLabel').textContent() === label, `${label} dashboard role remains locked`);
    await page.locator('#accountToggle').click();
    await page.locator('[data-account-action="logout"]').click();
    await page.locator('#demoLogin').waitFor({ state: 'visible' });
  }

  await page.reload({ waitUntil: 'networkidle' });
  await page.locator('#demoLogin').waitFor({ state: 'visible' });
  check(!(await page.locator('body').evaluate((body) => body.classList.contains('dark-mode'))), 'light theme persists after reload');
  console.log(`PASS  Browser E2E authentication and theme flow (${checks.length} assertions)`);
} finally {
  await browser.close();
}
