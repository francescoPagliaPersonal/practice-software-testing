import { Page, Locator } from '@playwright/test'
import { Routes } from './Routes'
import { BasePage } from './BasePage'

export class RegistrationPage extends BasePage {
	readonly heading: Locator
	readonly firstNameInput: Locator
	readonly lastNameInput: Locator
	readonly dobInput: Locator
	readonly streetInput: Locator
	readonly postalCodeInput: Locator
	readonly emailInput: Locator
	readonly passwordInput: Locator
	readonly phoneInput: Locator
	readonly cityInput: Locator
	readonly stateInput: Locator
	readonly countrySelect: Locator
	readonly submitButton: Locator
	readonly loginLink: Locator

	readonly firstNameError: Locator
	readonly lastNameError: Locator
	readonly dobError: Locator
	readonly streetError: Locator
	readonly postalCodeError: Locator
	readonly emailError: Locator
	readonly passwordError: Locator
	readonly registerError: Locator

	constructor(page: Page) {
		super(page)
		this.heading = page.locator('h3')
		this.firstNameInput = page.locator('[data-test="first-name"]')
		this.lastNameInput = page.locator('[data-test="last-name"]')
		this.dobInput = page.locator('[data-test="dob"]')
		this.streetInput = page.locator('[data-test="address"]')
		this.postalCodeInput = page.locator('[data-test="postcode"]')
		this.emailInput = page.locator('[data-test="email"]')
		this.passwordInput = page.locator('[data-test="password"]')
		this.phoneInput = page.locator('[data-test="phone"]')
		this.cityInput = page.locator('[data-test="city"]')
		this.stateInput = page.locator('[data-test="state"]')
		this.countrySelect = page.locator('[data-test="country"]')
		this.submitButton = page.locator('[data-test="register-submit"]')
		this.loginLink = page.locator('[data-test="login-link"]')

		this.firstNameError = page.locator('[data-test="first-name-error"]')
		this.lastNameError = page.locator('[data-test="last-name-error"]')
		this.dobError = page.locator('[data-test="dob-error"]')
		this.streetError = page.locator('[data-test="street-error"]')
		this.postalCodeError = page.locator('[data-test="postal_code-error"]')
		this.emailError = page.locator('[data-test="email-error"]')
		this.passwordError = page.locator('[data-test="password-error"]')
		this.registerError = page.locator('[data-test="register-error"]')
	}

	async openRegister() {
		await this.open(Routes.REGISTER)
		await this.heading.waitFor({ state: 'visible' })
	}

	async fillBasicInfo(params: {
		firstName: string
		lastName: string
		dob?: string
		street?: string
		postalCode?: string
		email: string
		password: string
		phone?: string
		city?: string
		state?: string
		country?: string
	}) {
		await this.firstNameInput.fill(params.firstName)
		await this.lastNameInput.fill(params.lastName)
		if (params.dob) await this.dobInput.fill(params.dob)
		if (params.street) await this.streetInput.fill(params.street)
		if (params.postalCode) await this.postalCodeInput.fill(params.postalCode)
		await this.emailInput.fill(params.email)
		await this.passwordInput.fill(params.password)
		if (params.phone) {
			await this.phoneInput.fill(params.phone)
		}
		if (params.city) await this.cityInput.fill(params.city)
		if (params.state) await this.stateInput.fill(params.state)
		if (params.country) await this.countrySelect.selectOption({ label: params.country })
	}

	async submit() {
		await this.submitButton.click()
	}

	async register(user: {
		firstName: string
		lastName: string
		dob?: string
		street?: string
		postalCode?: string
		email: string
		password: string
		phone?: string
		city?: string
		state?: string
		country?: string
	}) {
		await this.fillBasicInfo(user)
		await this.submit()
	}

	async getErrorText(locator: Locator) {
		await locator.waitFor({ state: 'visible' })
		return locator.innerText()
	}
}


