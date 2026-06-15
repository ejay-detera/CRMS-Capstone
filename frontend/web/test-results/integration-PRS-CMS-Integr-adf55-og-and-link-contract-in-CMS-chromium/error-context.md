# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: integration.spec.ts >> PRS & CMS Integration Module >> Positive: Create Activity, submit log, and link contract in CMS
- Location: e2e\integration.spec.ts:5:5

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: getByRole('link', { name: /Contract #\d+/ })
Expected: visible
Timeout: 15000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" with timeout 15000ms
  - waiting for getByRole('link', { name: /Contract #\d+/ })

```

```yaml
- complementary:
  - img "SBSI logo"
  - heading "Productivity Reports System" [level=2]
  - text: MAIN
  - navigation:
    - link "Dashboard":
      - /url: /prs/app/dashboard
      - img
      - text: Dashboard
    - link "Activity Log":
      - /url: /prs/app/activity-log
      - img
      - text: Activity Log
    - link "My Activities":
      - /url: /prs/app/my-activities
      - img
      - text: My Activities
    - link "Submit Activity":
      - /url: /prs/app/submit-activity
      - img
      - text: Submit Activity
    - link "My Performance":
      - /url: /prs/app/my-performance
      - img
      - text: My Performance
    - link "Leaderboard":
      - /url: /prs/app/leaderboard
      - img
      - text: Leaderboard
- banner:
  - button "Toggle Sidebar":
    - img
  - heading "Activity Log" [level=1]
  - img
  - searchbox "Search navigation"
  - text: June 2026
  - button "Notifications":
    - img
  - button "SM Sales Marketing Officer Employee":
    - text: SM Sales Marketing Officer Employee
    - img
- main:
  - text: Sales Workspace
  - heading "My Sales Activity Log" [level=1]
  - paragraph: Review your submitted product demonstrations, client presentations, contract closings, and validation pipeline.
  - text: TOTAL DEMO SUBMISSIONS 0
  - img
  - text: PIPELINE WIN RATE 0% 0 Won / 1 Total
  - img
  - text: FOLLOW-UP PENDING 0% 0 Activities
  - img
  - text: CONTRACT LOSS RATE 0% 0 Lost Opportunities
  - img
  - main:
    - img
    - textbox "Search logs by product or clinic..."
    - combobox:
      - option "All Statuses" [selected]
      - option "Approved (0)"
      - option "Pending (1)"
      - option "Rejected (0)"
    - combobox:
      - option "All Types" [selected]
      - option "Demo (0)"
      - option "Presentation (0)"
      - option "Closing (1)"
      - option "Others (0)"
    - text: Showing
    - strong: "1"
    - text: of
    - strong: "1"
    - text: activities
    - table:
      - rowgroup:
        - row "Date & Time Institution Name Contact Person Activity Scope SBU Segment Class Product Showcase Outcome Status Action":
          - columnheader "Date & Time"
          - columnheader "Institution Name"
          - columnheader "Contact Person"
          - columnheader "Activity Scope"
          - columnheader "SBU Segment"
          - columnheader "Class"
          - columnheader "Product Showcase"
          - columnheader "Outcome"
          - columnheader "Status"
          - columnheader "Action"
      - rowgroup:
        - row "Jun 16, 2026 4:29 AM - PUP Quezon City testing testing Closing No Contract SBU 3 Class B ReagentX N/A Pending View Details":
          - cell "Jun 16, 2026 4:29 AM -"
          - cell "PUP Quezon City"
          - cell "testing testing"
          - cell "Closing No Contract"
          - cell "SBU 3"
          - cell "Class B"
          - cell "ReagentX"
          - cell "N/A"
          - cell "Pending"
          - cell "View Details":
            - button "View Details"
    - text: Showing Page
    - strong: "1"
    - text: of
    - strong: "1"
    - button "Prev" [disabled]
    - button "1"
    - button "Next" [disabled]
  - text: Pending
  - heading "Sales Log Details" [level=3]
  - button:
    - img
  - text: "Log ID #29 Date & Duration Jun 16, 2026 (4:29 AM - ) Activity Scope Closing Product Showcase ReagentX Institution Clinic PUP Quezon City Contact Person testing testing SBU Segment SBU 3 Class Tier Class B Pipeline Outcome N/A Contract Status No contract linked yet Validated Approved By Attached Reference Files"
  - img
  - text: Business Plan Example (1).pdf Field Notes / Description
  - paragraph: test - integration to cms 1781555379104
  - text: Supervisor Validation Remarks Supervisor not recorded
  - paragraph: Pending Validation
  - button "Close"
- img
- img
```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test';
  2   | 
  3   | test.describe('PRS & CMS Integration Module', () => {
  4   | 
  5   |     test('Positive: Create Activity, submit log, and link contract in CMS', async ({ page }) => {
  6   |         const timestamp = Date.now();
  7   |         const contractSerial = `SN-2026-${timestamp}`;
  8   |         const dynamicNotes = `test - integration to cms ${timestamp}`;
  9   | 
  10  |         // 1. Login to Portal
  11  |         await page.goto('http://localhost:5173/');
  12  |         await expect(page.locator('h1')).toContainText('25 Years of Innovating Diagnostics Solutions');
  13  |         await page.getByRole('link', { name: 'Log in' }).click();
  14  | 
  15  |         await page.getByRole('textbox', { name: 'Email' }).fill('sales-marketing-officer@example.com');
  16  |         await page.getByRole('textbox', { name: 'Password' }).fill('password');
  17  |         await page.getByRole('button', { name: 'Sign In' }).click();
  18  | 
  19  |         // Wait for successful login redirection
  20  |         await page.waitForURL(/(?!.*login).*/, { timeout: 15000 });
  21  | 
  22  |         // 2. Open PRS (Productivity Report System) Module
  23  |         await page.locator('div').filter({ hasText: /^Open module$/ }).nth(4).click();
  24  |         await page.getByRole('link', { name: 'My Activities' }).click();
  25  | 
  26  |         // 3. Plan Activity
  27  |         await page.getByRole('button', { name: 'Plan Activity' }).click();
  28  |         await page.getByRole('textbox', { name: 'Start typing or pick an' }).click();
  29  |         await page.locator('div').filter({ hasText: /^PUP Quezon City$/ }).click();
  30  |         await page.getByRole('textbox', { name: 'Assigned contact person' }).fill('testing testing');
  31  |         await page.getByRole('combobox').first().selectOption('Closing');
  32  |         await page.getByRole('combobox').nth(1).selectOption('16:00 - 17:30');
  33  |         await page.getByRole('combobox').nth(2).selectOption('SBU 3');
  34  |         await page.getByRole('textbox', { name: 'Start typing or pick a product...' }).click();
  35  |         await page.getByText('ReagentX').click();
  36  |         await page.getByRole('combobox').nth(3).selectOption('Class B');
  37  |         await page.getByRole('textbox', { name: 'Write field objectives, items' }).fill(dynamicNotes);
  38  |         await page.getByRole('button', { name: 'Save to Itinerary' }).click();
  39  | 
  40  |         // 4. View Details of the newly created itinerary card & Submit Log
  41  |         const activityCard = page.locator('div').filter({ hasText: dynamicNotes });
  42  |         await activityCard.getByRole('button', { name: 'View Details' }).first().click();
  43  |         await page.getByRole('button', { name: 'Open in Submit Activity' }).click();
  44  | 
  45  |         // Upload verification document
  46  |         await page.locator('#multipleFilesInput').setInputFiles('Business Plan Example (1).pdf');
  47  |         await page.getByRole('button', { name: 'Submit Activity Log' }).click();
  48  |         await page.getByRole('button', { name: 'Yes, Submit Activity' }).click();
  49  |         await expect(page.getByRole('main')).toContainText('Submission Successful!', { timeout: 15000 });
  50  | 
  51  |         // 5. Navigate to CMS using "CRM / Contract Manager" link (opens in new tab)
  52  |         const page1Promise = page.waitForEvent('popup');
  53  |         await page.getByRole('button', { name: 'CRM / Contract Manager' }).click();
  54  |         const page1 = await page1Promise;
  55  | 
  56  |         // Wait for CMS Create Contract page to load
  57  |         await page1.waitForURL(/.*contracts\/create.*/, { timeout: 15000 });
  58  | 
  59  |         // 6. Complete contract fields (many are prefilled from PRS)
  60  |         await page1.getByRole('combobox').first().selectOption('Partnership Agreement');
  61  |         await page1.getByRole('textbox', { name: 'e.g. SN-2024-' }).fill(contractSerial);
  62  |         await page1.getByRole('combobox').nth(1).selectOption('Luzon');
  63  |         await page1.locator('input[type="date"]').first().fill('2026-06-16');
  64  |         await page1.locator('input[type="date"]').nth(1).fill('2032-12-16');
  65  | 
  66  |         // Upload contract document
  67  |         await page1.locator('input[type="file"]').setInputFiles('Business Plan Example (1).pdf');
  68  | 
  69  |         await page1.getByRole('button', { name: 'Create Contract' }).click();
  70  | 
  71  |         // Expect contract to be successfully created and details loaded
  72  |         await expect(page1.getByRole('heading', { name: 'PUP Quezon City' })).toBeVisible({ timeout: 15000 });
  73  | 
  74  |         // 7. Navigate to Activity Log and open the correct submitted row
  75  |         // NOTE: the activity log table does not show field notes as a column, so we filter
  76  |         // by the unique contractSerial which was linked via the CMS tab submission.
  77  |         // Fallback: filter by institution name and pick the most recently added row (last in table).
  78  |         await page.goto('http://localhost:5173/prs/app/activity-log');
  79  |         await page.waitForLoadState('networkidle', { timeout: 15000 });
  80  | 
  81  |         // Get the last row matching PUP Quezon City — most recent submission is at the bottom
  82  |         const allMatchingRows = page.getByRole('row').filter({ hasText: 'PUP Quezon City' });
  83  |         const rowCount = await allMatchingRows.count();
  84  |         const lastRow = allMatchingRows.nth(rowCount - 1);
  85  |         await lastRow.waitFor({ timeout: 15000 });
  86  |         await lastRow.getByRole('button').first().click();
  87  | 
  88  |         // 8. Wait for the Sales Log Details modal and assert the contract link is present
  89  |         // The modal CONTRACT STATUS section shows a link once a contract has been linked
  90  |         await expect(page.getByText('Sales Log Details')).toBeVisible({ timeout: 10000 });
  91  |         const contractLink = page.getByRole('link', { name: /Contract #\d+/ });
> 92  |         await expect(contractLink).toBeVisible({ timeout: 15000 });
      |                                    ^ Error: expect(locator).toBeVisible() failed
  93  | 
  94  |         const page2Promise = page.waitForEvent('popup');
  95  |         await contractLink.click();
  96  |         const page2 = await page2Promise;
  97  |         await expect(page2.getByRole('heading', { name: 'PUP Quezon City' })).toBeVisible({ timeout: 15000 });
  98  |     });
  99  | 
  100 |     test('Negative: Portal Activity Planning validations', async ({ page }) => {
  101 |         // 1. Login to Portal
  102 |         await page.goto('http://localhost:5173/');
  103 |         await page.getByRole('link', { name: 'Log in' }).click();
  104 |         await page.getByRole('textbox', { name: 'Email' }).fill('sales-marketing-officer@example.com');
  105 |         await page.getByRole('textbox', { name: 'Password' }).fill('password');
  106 |         await page.getByRole('button', { name: 'Sign In' }).click();
  107 |         await page.waitForURL(/(?!.*login).*/, { timeout: 15000 });
  108 | 
  109 |         // 2. Open My Activities
  110 |         await page.locator('div').filter({ hasText: /^Open module$/ }).nth(4).click();
  111 |         await page.getByRole('link', { name: 'My Activities' }).click();
  112 | 
  113 |         // 3. Plan Activity
  114 |         await page.getByRole('button', { name: 'Plan Activity' }).click();
  115 | 
  116 |         // 4. Try to submit empty form and check alert
  117 |         page.once('dialog', async dialog => {
  118 |             expect(dialog.message()).toContain('Please complete the institution, contact person, activity scope, schedule date, and field notes.');
  119 |             await dialog.dismiss();
  120 |         });
  121 |         await page.getByRole('button', { name: 'Save to Itinerary' }).click();
  122 | 
  123 |         // 5. Fill initial details but leave product empty for a Closing activity
  124 |         await page.getByRole('textbox', { name: 'Start typing or pick an' }).click();
  125 |         await page.locator('div').filter({ hasText: /^PUP Quezon City$/ }).click();
  126 |         await page.getByRole('textbox', { name: 'Assigned contact person' }).fill('testing testing');
  127 |         await page.getByRole('combobox').first().selectOption('Closing');
  128 |         await page.getByRole('textbox', { name: 'Write field objectives, items' }).fill('testing details');
  129 | 
  130 |         // Check product selection alert
  131 |         page.once('dialog', async dialog => {
  132 |             expect(dialog.message()).toContain('Please select a product showcase for this planned sales activity.');
  133 |             await dialog.dismiss();
  134 |         });
  135 |         await page.getByRole('button', { name: 'Save to Itinerary' }).click();
  136 | 
  137 |         // Cancel modal
  138 |         await page.getByRole('button', { name: 'Cancel' }).click();
  139 |     });
  140 | 
  141 |     test('Negative: Portal Activity Submission PDF requirement', async ({ page }) => {
  142 |         // 1. Login to Portal
  143 |         await page.goto('http://localhost:5173/');
  144 |         await page.getByRole('link', { name: 'Log in' }).click();
  145 |         await page.getByRole('textbox', { name: 'Email' }).fill('sales-marketing-officer@example.com');
  146 |         await page.getByRole('textbox', { name: 'Password' }).fill('password');
  147 |         await page.getByRole('button', { name: 'Sign In' }).click();
  148 |         await page.waitForURL(/(?!.*login).*/, { timeout: 15000 });
  149 | 
  150 |         // 2. Go to My Activities
  151 |         await page.locator('div').filter({ hasText: /^Open module$/ }).nth(4).click();
  152 |         await page.getByRole('link', { name: 'My Activities' }).click();
  153 | 
  154 |         // 3. Open Plan Activity Dialog and create a test activity
  155 |         const timestamp = Date.now();
  156 |         const dynamicNotes = `negative test activity ${timestamp}`;
  157 |         await page.getByRole('button', { name: 'Plan Activity' }).click();
  158 |         await page.getByRole('textbox', { name: 'Start typing or pick an' }).click();
  159 |         await page.locator('div').filter({ hasText: /^PUP Quezon City$/ }).click();
  160 |         await page.getByRole('textbox', { name: 'Assigned contact person' }).fill('testing testing');
  161 |         await page.getByRole('combobox').first().selectOption('Closing');
  162 |         await page.getByRole('textbox', { name: 'Start typing or pick a product...' }).click();
  163 |         await page.getByText('ReagentX').click();
  164 |         await page.getByRole('textbox', { name: 'Write field objectives, items' }).fill(dynamicNotes);
  165 |         await page.getByRole('button', { name: 'Save to Itinerary' }).click();
  166 | 
  167 |         // 4. Open in Submit Activity
  168 |         const activityCard = page.locator('div').filter({ hasText: dynamicNotes });
  169 |         await activityCard.getByRole('button', { name: 'View Details' }).first().click();
  170 |         await page.getByRole('button', { name: 'Open in Submit Activity' }).click();
  171 | 
  172 |         // 5. Try to submit without uploading attachment
  173 |         await page.getByRole('button', { name: 'Submit Activity Log' }).click();
  174 | 
  175 |         // Assert validation warning popup
  176 |         const alertOverlay = page.locator('.custom-alert-overlay.open');
  177 |         await expect(alertOverlay).toBeVisible();
  178 |         await expect(alertOverlay.locator('p')).toContainText('Please complete all required verification fields and mount at least one attachment.');
  179 |         await alertOverlay.locator('.btn-alert-ack').click();
  180 |     });
  181 | 
  182 |     test('Negative: CMS Contract Creation validation via Integration', async ({ page }) => {
  183 |         // 1. Login to Portal
  184 |         await page.goto('http://localhost:5173/');
  185 |         await page.getByRole('link', { name: 'Log in' }).click();
  186 |         await page.getByRole('textbox', { name: 'Email' }).fill('sales-marketing-officer@example.com');
  187 |         await page.getByRole('textbox', { name: 'Password' }).fill('password');
  188 |         await page.getByRole('button', { name: 'Sign In' }).click();
  189 |         await page.waitForURL(/(?!.*login).*/, { timeout: 15000 });
  190 | 
  191 |         // 2. Open My Activities
  192 |         await page.locator('div').filter({ hasText: /^Open module$/ }).nth(4).click();
```