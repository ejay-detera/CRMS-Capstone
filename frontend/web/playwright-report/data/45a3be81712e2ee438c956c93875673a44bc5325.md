# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: profile-change.spec.ts >> Profile Module >> Negative: Required fields validation
- Location: e2e\profile-change.spec.ts:50:5

# Error details

```
TimeoutError: locator.waitFor: Timeout 15000ms exceeded.
Call log:
  - waiting for locator('header').locator('.ring-2') to be visible

```

# Page snapshot

```yaml
- generic [ref=e3]:
  - navigation [ref=e4]:
    - generic [ref=e5]:
      - img "SBSI" [ref=e7]
      - link "Log in" [ref=e9] [cursor=pointer]:
        - /url: /login
  - generic [ref=e13]:
    - generic [ref=e14]: ISO 9001:2015 Certified
    - heading "25 Years of Innovating Diagnostics Solutions" [level=1] [ref=e16]
    - paragraph [ref=e18]: A unified platform for managing contracts, expenses, productivity, and support — built for the business finance and marketing industry.
    - link "Get started" [ref=e20] [cursor=pointer]:
      - /url: /login
  - generic [ref=e22]:
    - generic [ref=e23]: Features
    - heading "Everything your team needs" [level=2] [ref=e24]
    - paragraph [ref=e25]: Four integrated systems designed to streamline your operations from end to end.
    - generic [ref=e26]:
      - generic [ref=e27]:
        - img [ref=e31]
        - generic [ref=e34]:
          - heading "Contract Management" [level=3] [ref=e35]
          - paragraph [ref=e36]: Track, manage, and renew contracts with full audit trails and approval workflows.
      - generic [ref=e37]:
        - img [ref=e41]
        - generic [ref=e43]:
          - heading "Smart Expense Reimbursement" [level=3] [ref=e44]
          - paragraph [ref=e45]: Smart expense submissions with real-time status tracking and automated approvals.
      - generic [ref=e46]:
        - img [ref=e50]
        - generic [ref=e51]:
          - heading "Productivity Reports" [level=3] [ref=e52]
          - paragraph [ref=e53]: Generate detailed productivity analytics and performance summaries across teams.
      - generic [ref=e54]:
        - img [ref=e58]
        - generic [ref=e60]:
          - heading "Ticketing System" [level=3] [ref=e61]
          - paragraph [ref=e62]: Submit, escalate, and resolve support tickets with SLA breach monitoring built in.
  - contentinfo [ref=e63]:
    - generic [ref=e64]:
      - img "SBSI Logo" [ref=e65]
      - generic [ref=e66]: © 2026 Scientific Biotech Specialties, Inc. All rights reserved.
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | const LOGIN_URL = 'http://localhost:5173/';
  4  | const DASHBOARD_URL = 'http://localhost:5173/cms/sales/dashboard?state=/cms/sales/dashboard';
  5  | const EMAIL = 'sales-marketing-officer@example.com';
  6  | const PASSWORD = 'password';
  7  | 
  8  | async function loginAndGoToDashboard(page: any) {
  9  |     await page.goto(LOGIN_URL);
  10 |     await page.getByRole('link', { name: 'Log in' }).click();
  11 |     await page.getByRole('textbox', { name: 'Email' }).fill(EMAIL);
  12 |     await page.getByRole('textbox', { name: 'Password' }).fill(PASSWORD);
  13 |     await page.getByRole('button', { name: 'Sign In' }).click();
  14 |     await page.waitForURL(/(?!.*login).*/, { timeout: 15000 });
  15 |     await page.goto(DASHBOARD_URL);
  16 |     await page.waitForLoadState('networkidle', { timeout: 15000 });
  17 | }
  18 | 
  19 | async function openMyProfile(page: any) {
> 20 |     await page.locator('header').locator('.ring-2').waitFor({ timeout: 15000 });
     |                                                     ^ TimeoutError: locator.waitFor: Timeout 15000ms exceeded.
  21 |     await page.locator('header').locator('.ring-2').click();
  22 |     await page.getByRole('button', { name: 'My Profile' }).click();
  23 | }
  24 | 
  25 | async function logout(page: any) {
  26 |     await page.locator('header').locator('.ring-2').waitFor({ timeout: 10000 });
  27 |     await page.locator('header').locator('.ring-2').click();
  28 |     await page.getByRole('button', { name: 'Logout' }).click();
  29 | }
  30 | 
  31 | test.describe('Profile Module', () => {
  32 | 
  33 |     test('Positive: Update personal details and logout', async ({ page }) => {
  34 |         await loginAndGoToDashboard(page);
  35 |         await openMyProfile(page);
  36 | 
  37 |         // Update profile fields
  38 |         await page.getByRole('textbox', { name: 'e.g. Shadrack' }).fill('Sales');
  39 |         await page.getByRole('textbox', { name: 'e.g. Castro' }).fill('Employee');
  40 |         await page.getByRole('textbox', { name: 'e.g. Miguel' }).fill('Testing');
  41 |         await page.getByRole('textbox', { name: 'you@sbsi.com' }).fill('sales-marketing-officer@gmail.com');
  42 |         await page.getByRole('textbox', { name: '+63 2 8xxx xxxx' }).fill('09914159183');
  43 | 
  44 |         await page.getByRole('button', { name: 'Save changes' }).click();
  45 |         await expect(page.getByText('Your personal details have')).toBeVisible({ timeout: 10000 });
  46 | 
  47 |         await logout(page);
  48 |     });
  49 | 
  50 |     test('Negative: Required fields validation', async ({ page }) => {
  51 |         await loginAndGoToDashboard(page);
  52 |         await openMyProfile(page);
  53 | 
  54 |         // Clear required fields and attempt to save
  55 |         await page.getByRole('textbox', { name: 'e.g. Shadrack' }).fill('');
  56 |         await page.getByRole('textbox', { name: 'e.g. Castro' }).fill('');
  57 |         await page.getByRole('button', { name: 'Save changes' }).click();
  58 | 
  59 |         await expect(page.locator('text=First name is required.')).toBeVisible({ timeout: 5000 });
  60 |         await expect(page.locator('text=Last name is required.')).toBeVisible({ timeout: 5000 });
  61 | 
  62 |         await logout(page);
  63 |     });
  64 | 
  65 |     test('Negative: Invalid email format validation', async ({ page }) => {
  66 |         await loginAndGoToDashboard(page);
  67 |         await openMyProfile(page);
  68 | 
  69 |         await page.getByRole('textbox', { name: 'you@sbsi.com' }).fill('not-a-valid-email');
  70 |         await page.getByRole('button', { name: 'Save changes' }).click();
  71 | 
  72 |         await expect(page.locator('text=Enter a valid email address.')).toBeVisible({ timeout: 5000 });
  73 | 
  74 |         await logout(page);
  75 |     });
  76 | 
  77 |     test('Negative: Invalid phone number validation', async ({ page }) => {
  78 |         await loginAndGoToDashboard(page);
  79 |         await openMyProfile(page);
  80 | 
  81 |         await page.getByRole('textbox', { name: '+63 2 8xxx xxxx' }).fill('123');
  82 |         await page.getByRole('button', { name: 'Save changes' }).click();
  83 | 
  84 |         await expect(page.locator('text=Phone number must be 7–11 digits.')).toBeVisible({ timeout: 5000 });
  85 | 
  86 |         await logout(page);
  87 |     });
  88 | 
  89 | });
```