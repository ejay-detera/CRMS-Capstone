# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: contract.spec.ts >> test
- Location: e2e\contract.spec.ts:3:1

# Error details

```
Test timeout of 30000ms exceeded.
```

```
Error: locator.click: Test timeout of 30000ms exceeded.
Call log:
  - waiting for getByRole('button', { name: 'Sign In' })

```

# Page snapshot

```yaml
- generic [ref=e3]:
  - banner [ref=e4]:
    - generic [ref=e5]:
      - img "SBSI" [ref=e7]
      - generic [ref=e8]:
        - generic [ref=e9]:
          - generic [ref=e10]: FM
          - generic [ref=e11]:
            - generic [ref=e12]: Finance Manager
            - generic [ref=e13]: Finance Manager
        - button "Logout" [ref=e14] [cursor=pointer]:
          - img [ref=e15]
          - generic [ref=e18]: Logout
  - main [ref=e19]:
    - generic [ref=e20]:
      - generic [ref=e21]:
        - paragraph [ref=e22]: Good evening,
        - heading "Finance Manager Finance Manager" [level=1] [ref=e23]:
          - text: Finance Manager
          - generic [ref=e24]: Finance Manager
        - paragraph [ref=e25]: Select a subsystem below to get started.
      - generic [ref=e26]:
        - generic [ref=e27] [cursor=pointer]:
          - generic [ref=e28]:
            - img [ref=e30]
            - heading "Contract Management" [level=3] [ref=e33]
          - paragraph [ref=e35]: Track, manage, and renew contracts with full audit trails and automated approval workflows — all in one place.
          - generic [ref=e37]:
            - generic [ref=e38]: Open module
            - img [ref=e39]
        - generic [ref=e41] [cursor=pointer]:
          - generic [ref=e42]:
            - img [ref=e44]
            - heading "Smart Expense Reimbursement" [level=3] [ref=e46]
          - paragraph [ref=e48]: Submit and track expense reimbursements with automated approval workflows and real-time status updates.
          - generic [ref=e50]:
            - generic [ref=e51]: Open module
            - img [ref=e52]
        - generic [ref=e54] [cursor=pointer]:
          - generic [ref=e55]:
            - img [ref=e57]
            - heading "Productivity Report System" [level=3] [ref=e58]
          - paragraph [ref=e60]: Generate detailed productivity analytics and performance summaries across teams with exportable insights.
          - generic [ref=e62]:
            - generic [ref=e63]: Open module
            - img [ref=e64]
        - generic [ref=e66] [cursor=pointer]:
          - generic [ref=e67]:
            - img [ref=e69]
            - heading "Ticketing System" [level=3] [ref=e74]
          - paragraph [ref=e76]: Submit, escalate, and resolve support tickets with SLA breach monitoring and priority management tools.
          - generic [ref=e78]:
            - generic [ref=e79]: Open module
            - img [ref=e80]
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | test('test', async ({ page }) => {
  4  |   await page.goto('http://localhost:5173/');
  5  |   await page.getByRole('link', { name: 'Log in' }).click();
  6  |   await expect(page.getByRole('heading', { name: 'Welcome Back!' })).toBeVisible();
  7  |   await page.getByRole('textbox', { name: 'Email' }).click();
  8  |   await page.getByRole('textbox', { name: 'Email' }).fill('finance-manager@example.com');
  9  |   await page.getByRole('textbox', { name: 'Password' }).click();
  10 |   await page.getByRole('textbox', { name: 'Password' }).fill('password');
  11 |   await page.getByRole('textbox', { name: 'Password' }).press('Enter');
> 12 |   await page.getByRole('button', { name: 'Sign In' }).click();
     |                                                       ^ Error: locator.click: Test timeout of 30000ms exceeded.
  13 |   await expect(page.getByRole('heading', { name: 'Finance Manager Finance' })).toBeVisible();
  14 |   await page.getByText('Contract ManagementTrack,').click();
  15 |   await expect(page.getByRole('heading', { name: 'Good evening, Finance.' })).toBeVisible();
  16 |   await page.getByRole('button', { name: 'Contracts' }).click();
  17 |   await expect(page.getByRole('heading', { name: 'All Contracts', exact: true })).toBeVisible();
  18 |   await page.getByRole('button').nth(4).click();
  19 |   await expect(page.getByRole('heading', { name: 'Create New Contract' })).toBeVisible();
  20 |   await page.getByRole('textbox', { name: 'e.g. Globe Telecom' }).click();
  21 |   await page.getByRole('button', { name: 'test', exact: true }).click();
  22 |   await page.getByRole('combobox').first().selectOption('Service Agreement');
  23 |   await page.getByRole('textbox', { name: 'e.g. ITM-' }).click();
  24 |   await page.getByRole('textbox', { name: 'e.g. ITM-' }).fill('6');
  25 |   await page.getByRole('textbox', { name: 'e.g. Network Infrastructure' }).click();
  26 |   await page.getByRole('textbox', { name: 'e.g. Network Infrastructure' }).fill('67');
  27 |   await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).click();
  28 |   await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).fill('7');
  29 |   await page.getByRole('textbox', { name: 'e.g. SBU-' }).click();
  30 |   await page.getByRole('textbox', { name: 'e.g. SBU-' }).fill('6767');
  31 |   await page.getByRole('combobox').nth(1).selectOption('Luzon');
  32 |   await page.locator('input[type="date"]').first().fill('2026-06-12');
  33 |   await page.locator('input[type="date"]').nth(1).fill('2028-06-12');
  34 |   await page.getByRole('button', { name: 'Create Contract' }).click();
  35 |   await page.getByRole('button').filter({ hasText: /^$/ }).click();
  36 |   await page.locator('#reka-dropdown-menu-trigger-v-59').click();
  37 |   await page.getByRole('menuitem', { name: 'Edit contract' }).click();
  38 |   await page.getByRole('textbox', { name: 'e.g. Network Infrastructure' }).click();
  39 |   await page.getByRole('textbox', { name: 'e.g. ITM-' }).click();
  40 |   await page.getByRole('textbox', { name: 'e.g. ITM-' }).fill('67');
  41 |   await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).click();
  42 |   await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).click();
  43 |   await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).press('ArrowLeft');
  44 |   await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).fill('67');
  45 |   await page.getByRole('button', { name: 'Save Changes' }).click();
  46 |   await expect(page.getByText('Contract updated')).toBeVisible();
  47 |   await expect(page.getByText('67').first()).toBeVisible();
  48 |   await expect(page.getByText('67').nth(2)).toBeVisible();
  49 |   await page.getByRole('button').filter({ hasText: /^$/ }).click();
  50 |   await page.getByText('FMFinance ManagerFinance').click();
  51 |   await page.getByRole('button', { name: 'Logout' }).click();
  52 | });
```