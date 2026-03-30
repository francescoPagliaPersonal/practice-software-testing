import { test, expect } from '@playwright/test'
import { RegistrationPage } from './pages/RegistrationPage'
import { faker } from '@faker-js/faker'


test.describe('Registration Feature', () => {
	let registrationPage: RegistrationPage

	test.beforeEach(async ({ page }) => {
		registrationPage = new RegistrationPage(page)
		await registrationPage.openRegister()
		await expect(page.locator('h3')).toContainText('Customer registration')
	})

	async function fillWithBaselineValidData(page: RegistrationPage, overrides?: Partial<Parameters<RegistrationPage['fillBasicInfo']>[0]>) {
		const base = {
			firstName: "John",
			lastName: "Doe",
			dob: faker.date.birthdate({ min: 18, max: 75, mode: 'age' }).toISOString().split('T')[0],
			street: faker.location.streetAddress(),
			postalCode: faker.location.zipCode(),
			city: faker.location.city(),
			state: faker.location.state({ abbreviated: true }),
			country: 'Austria',
			phone: faker.phone.number().replace(/[^0-9]/g, '').slice(0, 10),
			email: "jdoe@gmail.com",
			password: "AAAAbb1!v!",
		}
		await page.fillBasicInfo({ ...base, ...(overrides || {}) })
		return { ...base, ...(overrides || {}) }
	}

	test('REG-TC-001 - successful registration with all valid data @smoke', async ({ page }) => {
		await fillWithBaselineValidData(registrationPage)
		await registrationPage.submit()
		await expect(page).toHaveURL(/(account|auth\/login)/)
	})
});