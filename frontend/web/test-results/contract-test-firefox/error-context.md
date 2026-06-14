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
          - generic [ref=e19]: Logout
  - main [ref=e20]:
    - generic [ref=e21]:
      - generic [ref=e22]:
        - paragraph [ref=e23]: Good evening,
        - heading "Finance Manager Finance Manager" [level=1] [ref=e24]:
          - text: Finance Manager
          - generic [ref=e25]: Finance Manager
        - paragraph [ref=e26]: Select a subsystem below to get started.
      - generic [ref=e27]:
        - generic [ref=e28] [cursor=pointer]:
          - generic [ref=e29]:
            - img [ref=e31]
            - heading "Contract Management" [level=3] [ref=e36]
          - paragraph [ref=e38]: Track, manage, and renew contracts with full audit trails and automated approval workflows — all in one place.
          - generic [ref=e40]:
            - generic [ref=e41]: Open module
            - img [ref=e42]
        - generic [ref=e45] [cursor=pointer]:
          - generic [ref=e46]:
            - img [ref=e48]
            - heading "Smart Expense Reimbursement" [level=3] [ref=e51]
          - paragraph [ref=e53]: Submit and track expense reimbursements with automated approval workflows and real-time status updates.
          - generic [ref=e55]:
            - generic [ref=e56]: Open module
            - img [ref=e57]
        - generic [ref=e60] [cursor=pointer]:
          - generic [ref=e61]:
            - img [ref=e63]
            - heading "Productivity Report System" [level=3] [ref=e67]
          - paragraph [ref=e69]: Generate detailed productivity analytics and performance summaries across teams with exportable insights.
          - generic [ref=e71]:
            - generic [ref=e72]: Open module
            - img [ref=e73]
        - generic [ref=e76] [cursor=pointer]:
          - generic [ref=e77]:
            - img [ref=e79]
            - heading "Ticketing System" [level=3] [ref=e85]
          - paragraph [ref=e87]: Submit, escalate, and resolve support tickets with SLA breach monitoring and priority management tools.
          - generic [ref=e89]:
            - generic [ref=e90]: Open module
            - img [ref=e91]
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