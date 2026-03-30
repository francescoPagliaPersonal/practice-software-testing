import { Page, Locator } from '@playwright/test'
import { BasePage } from './BasePage'
import { Routes } from './Routes'

export class LoginPage extends BasePage {
	readonly emailInput: Locator
	readonly passwordInput: Locator
	readonly loginButton: Locator
	readonly showPasswordButton: Locator
	readonly loginError: Locator
	readonly emailError: Locator
	readonly registerLink: Locator
	readonly forgotPasswordLink: Locator
	readonly passwordError: Locator
	readonly googleSignInButton: Locator

	constructor(page: Page) {
		super(page)
		this.emailInput = page.locator('[data-test="email"]')
		this.passwordInput = page.locator('[data-test="password"]')
		this.loginButton = page.locator('[data-test="login-submit"]')
		this.showPasswordButton = page.locator('.input-group-append button')
		this.loginError = page.locator('[data-test="login-error"]')
		this.emailError = page.locator('[data-test="email-error"]')
		this.registerLink = page.locator('[data-test="register-link"]')
		this.forgotPasswordLink = page.locator('[data-test="forgot-password-link"]')
		this.passwordError = page.locator('[data-test="password-error"]')
		this.googleSignInButton = page.locator('button:has-text("Sign in with Google")').first()
	}

	async open() {
		await super.open(Routes.LOGIN)
	}

	async login(email: string, pass: string) {
		await this.emailInput.fill(email)
		await this.passwordInput.fill(pass)
		await this.loginButton.click()
	}

	async clickShowPassword() {
		await this.showPasswordButton.click()
	}

	async clickRegister() {
		await this.registerLink.click()
	}

	async clickForgotPassword() {
		await this.forgotPasswordLink.click()
	}

	async getLoginErrorText() {
		await this.loginError.waitFor({ state: 'visible' })
		return await this.loginError.innerText()
	}

	async getEmailErrorText() {
		await this.emailError.waitFor({ state: 'visible' })
		return await this.emailError.innerText()
	}

	async getPasswordErrorText() {
		await this.passwordError.waitFor({ state: 'visible' })
		return await this.passwordError.innerText()
	}
}