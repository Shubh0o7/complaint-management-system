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

  await page.locator('#demoLoginForm button[type="submit"]').click();
  await page.locator('.app-shell').waitFor({ state: 'visible' });
  check(await page.locator('#accountRole').textContent() === 'Complainant', 'student credentials open complainant workspace');
  check((await page.locator('#overviewView').textContent()).includes('Complainant workspace'), 'complainant workspace content is rendered');
  check(await page.locator('#roleSelectLabel').textContent() === 'Complainant', 'authenticated role is displayed as locked');
  check(await page.locator('#roleSelect').count() === 0, 'post-login role selector is not available');

  await page.locator('#themeToggle').click();
  check(!(await page.locator('body').evaluate((body) => body.classList.contains('dark-mode'))), 'light mode restores from authenticated shell');
  check(await page.evaluate(() => localStorage.getItem('campusresolve-demo-theme-v1')) === 'light', 'light mode persists to localStorage');

  await page.locator('#accountToggle').click();
  await page.locator('[data-account-action="logout"]').click();
  await page.locator('#demoLogin').waitFor({ state: 'visible' });
  check(await page.locator('.app-shell').evaluate((el) => el.classList.contains('hidden')), 'logout returns to login screen');
  check(await page.evaluate(() => sessionStorage.getItem('campusresolve-demo-auth-v1')) === null, 'logout clears demo session storage');

  const roleAccounts = [
    ['admin', 'admin@campus.edu', 'Administrator'],
    ['department', 'manager@campus.edu', 'Department Manager'],
    ['officer', 'officer@campus.edu', 'Complaint Officer']
  ];
  for (const [role, email, label] of roleAccounts) {
    await page.locator(`[data-demo-role="${role}"]`).click();
    check(await page.locator('#demoEmail').inputValue() === email, `${label} email is prefilled`);
    await page.locator('#demoLoginForm button[type="submit"]').click();
    await page.locator('.app-shell').waitFor({ state: 'visible' });
    check(await page.locator('#accountRole').textContent() === label, `${label} credentials open only the assigned dashboard`);
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
