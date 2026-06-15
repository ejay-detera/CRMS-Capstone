# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: ejay-testing.spec.ts >> test
- Location: e2e\ejay-testing.spec.ts:3:1

# Error details

```
TimeoutError: locator.click: Timeout 10000ms exceeded.
Call log:
  - waiting for getByRole('button', { name: 'Edit Contract' })

```

# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - main [ref=e4]:
    - generic [ref=e5]:
      - generic [ref=e8]:
        - generic [ref=e10]:
          - img "SBSI logo" [ref=e11]
          - img "SBSI logo" [ref=e12]
        - generic [ref=e13]:
          - generic [ref=e14]:
            - generic [ref=e15]: Main
            - list [ref=e17]:
              - listitem [ref=e18]:
                - link "Dashboard" [ref=e19] [cursor=pointer]:
                  - /url: /cms/manager/dashboard
                  - img [ref=e20]
                  - generic [ref=e25]: Dashboard
          - generic [ref=e26]:
            - generic [ref=e27]: Contracts
            - list [ref=e29]:
              - listitem [ref=e30]:
                - button "Contracts" [ref=e31]:
                  - img [ref=e32]
                  - generic [ref=e35]: Contracts
                  - img [ref=e36]
                - list [ref=e38]:
                  - listitem [ref=e39]:
                    - link "All Contracts" [ref=e40] [cursor=pointer]:
                      - /url: /cms/manager/contracts
                      - img [ref=e41]
                      - generic [ref=e44]: All Contracts
                  - listitem [ref=e45]:
                    - link "Contract Requests" [ref=e46] [cursor=pointer]:
                      - /url: /cms/manager/contract-requests
                      - img [ref=e47]
                      - generic [ref=e50]: Contract Requests
          - generic [ref=e51]:
            - generic [ref=e52]: Vendor Management
            - list [ref=e54]:
              - listitem [ref=e55]:
                - link "Business Partners" [ref=e56] [cursor=pointer]:
                  - /url: /cms/manager/partners
                  - img [ref=e57]
                  - generic [ref=e62]: Business Partners
          - generic [ref=e63]:
            - generic [ref=e64]: General
            - list [ref=e66]:
              - listitem [ref=e67]:
                - link "Notifications" [ref=e68] [cursor=pointer]:
                  - /url: /cms/manager/notifications
                  - img [ref=e69]
                  - generic [ref=e72]: Notifications
        - generic [ref=e74] [cursor=pointer]:
          - img [ref=e75]
          - generic [ref=e78]: Logout
      - main [ref=e79]:
        - generic [ref=e80]:
          - generic [ref=e81]:
            - button "Toggle Sidebar" [ref=e82]:
              - img
              - generic [ref=e83]: Toggle Sidebar
            - generic [ref=e84]:
              - img [ref=e85]
              - textbox "Search data..." [ref=e88]
          - generic [ref=e89]:
            - button "3" [ref=e90]:
              - img [ref=e91]
              - generic [ref=e94]: "3"
            - generic [ref=e96] [cursor=pointer]:
              - generic [ref=e98]: SM
              - generic [ref=e99]:
                - paragraph [ref=e100]: Sales Marketing Manager
                - paragraph [ref=e101]: Manager
  - img [ref=e146]
```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test';
  2   | 
  3   | test('test', async ({ page }) => {
  4   | 
  5   |     // Dynamic test data
  6   |     const timestamp = Date.now();
  7   |     const vendor = {
  8   |         name: `Testing Company ${ timestamp }`,
  9   |         updatedName: `Testing Company ${ timestamp } Updated`,
  10  |         industry: 'healthcare',
  11  |         region: 'Luzon',
  12  |         contactPerson: 'Test Person',
  13  |         contactNumber: '09914159183',
  14  |         email: `test+${ timestamp }@example.com`,
  15  |         address: 'Test City',
  16  |     };
  17  |     const contract = {
  18  |         vendorName: `Testing Vendor ${ timestamp }`,
  19  |         updatedVendorName: `Testing Vendor ${ timestamp } Updated`,
  20  |         type: 'Service Agreement',
  21  |         itm: `ITM-${ timestamp }`,
  22  |         description: 'Network Infrastructure',
  23  |         serialNumber: `SN-2026-${ timestamp }`,
  24  |         sbu: `SBU-${ timestamp }`,
  25  |         region: 'Visayas',
  26  |         startDate: '2026-06-14',
  27  |         endDate: '2032-12-14',
  28  |     };
  29  | 
  30  | //Login as Finance Manager
  31  | await page.goto('http://localhost:5173/');
  32  | await page.getByRole('link', { name: 'Log in' }).click();
  33  | await page.getByRole('textbox', { name: 'Email' }).fill('sales-marketing-manager@example.com');
  34  | await page.getByRole('textbox', { name: 'Password' }).fill('password');
  35  | await page.getByRole('button', { name: 'Sign In' }).click();
  36  | await page.waitForURL(/(?!.*login).*/, { timeout: 10000 });
  37  | 
  38  | //Contract Module as Finance Manager
  39  | //Create Contract
  40  | await expect(page.locator('h1')).toContainText('Sales Marketing Manager Manager', { timeout: 10000 });
  41  | await page.getByText('Open module').first().click();
  42  | await page.getByRole('button', { name: 'Contracts' }).click();
  43  | await page.getByRole('button').nth(4).click();
  44  | await page.getByRole('textbox', { name: 'e.g. Globe Telecom' }).fill(contract.vendorName);
  45  | await page.getByRole('combobox').first().selectOption(contract.type);
  46  | await page.getByRole('textbox', { name: 'e.g. ITM-' }).fill(contract.itm);
  47  | await page.getByRole('textbox', { name: 'e.g. Network Infrastructure' }).fill(contract.description);
  48  | await page.getByRole('textbox', { name: 'e.g. SN-2024-' }).fill(contract.serialNumber);
  49  | await page.getByRole('textbox', { name: 'e.g. SBU-' }).fill(contract.sbu);
  50  | await page.getByRole('combobox').nth(1).selectOption(contract.region);
  51  | await page.locator('input[type="date"]').first().fill(contract.startDate);
  52  | await page.locator('input[type="date"]').nth(1).fill(contract.endDate);
  53  | await page.getByRole('button', { name: 'Create Contract' }).click();
  54  | 
  55  | //Edit Contract
> 56  | await page.getByRole('button', { name: 'Edit Contract' }).click();
      |                                                           ^ TimeoutError: locator.click: Timeout 10000ms exceeded.
  57  | await page.getByRole('textbox', { name: 'e.g. Globe Telecom' }).fill(contract.updatedVendorName);
  58  | await page.getByRole('button', { name: 'Save Changes' }).click();
  59  | await expect(page.locator('h1')).toContainText(contract.updatedVendorName);
  60  | await page.getByRole('button').nth(3).click();
  61  | await page.locator('div').filter({ hasText: /^Logout$/ }).nth(1).click();
  62  | 
  63  | //Login as Finance Admin
  64  | //Vendor Module as Finance Admin
  65  | await page.getByRole('link', { name: 'Log in' }).click();
  66  | await page.getByRole('textbox', { name: 'Email' }).fill('sales-marketing-admin@example.com');
  67  | await page.getByRole('textbox', { name: 'Password' }).fill('password');
  68  | await page.getByRole('button', { name: 'Sign In' }).click();
  69  | await page.waitForURL(/(?!.*login).*/, { timeout: 10000 });
  70  | await expect(page.locator('h1')).toContainText('Sales Marketing Administrator Admin', { timeout: 10000 });
  71  | await page.getByText('Open module').first().click();
  72  | await page.getByRole('link', { name: 'Business & Suppliers' }).click();
  73  | 
  74  | //Create Business Partner
  75  | await page.locator('#add-partner-btn').click();
  76  | await page.getByRole('textbox', { name: 'Organization name' }).fill(vendor.name);
  77  | await page.getByRole('textbox', { name: 'e.g. Healthcare' }).fill(vendor.industry);
  78  | await page.getByRole('combobox').first().selectOption(vendor.region);
  79  | await page.getByRole('textbox', { name: 'Full name' }).fill(vendor.contactPerson);
  80  | await page.getByRole('textbox', { name: '09xxxxxxxxx' }).fill(vendor.contactNumber);
  81  | await page.getByText('Contact DetailsContact Person').click();
  82  | await page.getByRole('textbox', { name: 'contact@company.com' }).fill(vendor.email);
  83  | await page.getByRole('textbox', { name: 'Street, City' }).fill(vendor.address);
  84  | await page.getByRole('button', { name: 'Add Partner' }).click();
  85  | await expect(page.getByRole('button', { name: new RegExp(`Active ${ vendor.name }`) })).toBeVisible({ timeout: 20000 });
  86  | 
  87  | //Edit Business Partner
  88  | const partnerCard = page.getByRole('button', { name: new RegExp(`Active ${ vendor.name }`) });
  89  | await partnerCard.hover();
  90  | await page.locator('[id^="reka-dropdown-menu-trigger"]').first().click();
  91  | await page.getByRole('menuitem', { name: 'View details' }).click();
  92  | await page.getByRole('button', { name: 'Edit' }).click();
  93  | await page.getByRole('textbox', { name: 'Organization name' }).fill(vendor.updatedName);
  94  | await page.getByRole('button', { name: 'Save Changes' }).click();
  95  | await expect(page.locator('h1')).not.toContainText('Edit Business Partner', { timeout: 10000 });
  96  | await expect(page.locator('h1')).toContainText(vendor.updatedName, { timeout: 10000 });
  97  | await page.getByRole('button', { name: 'Back to Partners' }).click();
  98  | 
  99  | //Delete Business Partner
  100 | const updatedPartnerCard = page.getByRole('button', { name: new RegExp(`Active ${ vendor.updatedName }`) });
  101 | await expect(updatedPartnerCard).toBeVisible({ timeout: 10000 });
  102 | await updatedPartnerCard.hover();
  103 | await page.locator('[id^="reka-dropdown-menu-trigger"]').first().click();
  104 | await page.getByRole('menuitem', { name: 'Delete' }).click();
  105 | await page.getByRole('button', { name: 'Delete' }).click();
  106 | 
  107 | //Go to Contract Module and Delete the Contract
  108 | await page.getByRole('link', { name: 'Contracts' }).click();
  109 | const contractRow = page.getByRole('row').filter({ hasText: contract.updatedVendorName });
  110 | await contractRow.waitFor({ timeout: 10000 });
  111 | await contractRow.hover();
  112 | await contractRow.getByRole('button').click();
  113 | 
  114 | await page.getByRole('menuitem', { name: 'Delete' }).click();
  115 | await expect(page.getByText('Contract removed')).toBeVisible({ timeout: 10000 });
  116 | 
  117 | //Logout (End of test)
  118 | await page.locator('div').filter({ hasText: /^Logout$/ }).nth(1).click();
  119 | });
```