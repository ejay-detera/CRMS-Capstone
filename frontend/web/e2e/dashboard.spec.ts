import { test, expect, type Page, type Route } from '@playwright/test'
import { mockLogin } from './helpers/auth'

test.describe('Admin Dashboard', () => {
  test.beforeEach(async ({ page }: { page: Page }) => {
    // 1. Intercept contract dashboard API requests
    await page.route('**/api/dashboard', async (route: Route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              contract_id: 'CON-001',
              bp_name: 'Acme Corporation',
              category: 'Service Agreement',
              item_code: 'SRV-001',
              description: 'General IT services',
              serial_number: 'SN12345',
              sbu_number: 'SBU-10',
              region: 'Luzon',
              start_date: '2026-01-01',
              end_date: '2026-12-31',
              approval_status: 'Approved',
              workflowStatus: 'Completed',
              documents: []
            }
          ]
        })
      })
    })

    // 2. Intercept auth-service admin/users list request
    await page.route('**/admin/users?*', async (route: Route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 1,
              email: 'admin@crms-test.com',
              is_active: true,
              profile: {
                first_name: 'Test',
                last_name: 'Admin',
                role: { name: 'Admin' }
              }
            },
            {
              id: 2,
              email: 'manager@crms-test.com',
              is_active: true,
              profile: {
                first_name: 'Jane',
                last_name: 'Manager',
                role: { name: 'Manager' }
              }
            }
          ]
        })
      })
    })

    // 3. Intercept contract-management audit logs request
    await page.route('**/audit-logs?*', async (route: Route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              action: 'Contract Created',
              description: 'Created contract CON-001',
              user_name: 'Test Admin',
              performed_at: new Date().toISOString()
            }
          ]
        })
      })
    })

    // 4. Bypass auth redirects by pre-populating localStorage
    await mockLogin(page, 'Admin')
  })

  test('should display dashboard UI sections and fetch mock data', async ({ page }: { page: Page }) => {
    // Navigate to admin dashboard
    await page.goto('admin/dashboard')

    // Verify correct routing
    await expect(page).toHaveURL(/admin\/dashboard/)

    // Assert key headers and layout sections are visible
    await expect(page.locator('text=Admin Portal')).toBeVisible()
    await expect(page.locator('text=Here\'s what\'s happening at SBSI today.')).toBeVisible()

    // Assert that card stats exist (e.g. Total Contracts)
    await expect(page.locator('text=Total Contracts')).toBeVisible()
    await expect(page.locator('text=Total Employees')).toBeVisible()

    // Assert Table headers and content
    await expect(page.locator('text=Recent contracts')).toBeVisible()
    await expect(page.locator('text=Acme Corporation')).toBeVisible() // From our mocked contracts list

    // Assert Audit log section
    await expect(page.locator('text=Audit log')).toBeVisible()
    await expect(page.locator('text=Created contract CON-001')).toBeVisible() // From our mocked audit logs

    // Assert User list section
    await expect(page.locator('text=User list')).toBeVisible()
    await expect(page.locator('text=admin@crms-test.com')).toBeVisible() // From our mocked users list
  })
})
