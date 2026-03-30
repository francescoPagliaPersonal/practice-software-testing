import { test, expect } from '@playwright/test'
import { LoginPage } from './pages/LoginPage'

test.describe('Login Page - based on LoginTestCases.md', () => {
	let loginPage: LoginPage
	const validEmail = 'jdoe@gmail.com'
	const validPassword = 'AAAAbb1!v!'

	test.beforeEach(async ({ page }) => {
		loginPage = new LoginPage(page)
		await loginPage.open()
	})

	test('REG-TC-002 - successful login with valid credentials @smoke', async ({ page }) => {
		await loginPage.login(validEmail, validPassword)

		await expect(page).toHaveURL(/account/)
		await expect(page.locator('h1')).toContainText('My account')
	})
})