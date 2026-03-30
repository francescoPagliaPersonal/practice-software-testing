import { Page, Locator } from '@playwright/test'

import { Routes } from './Routes'

export class BasePage {
	readonly page: Page
	readonly headerLogo: Locator

	constructor(page: Page) {
		this.page = page
		this.headerLogo = page.locator('.navbar-brand')
	}

	async open(path: string = Routes.HOME) {
		await this.page.goto(path)
	}

	async waitForUrl(urlPart: string) {
		await this.page.waitForURL(`**/${urlPart}`)
	}
}