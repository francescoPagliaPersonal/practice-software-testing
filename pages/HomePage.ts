import { Page, Locator } from '@playwright/test';

export class HomePage {
    readonly page: Page;
    readonly searchInput: Locator;
    readonly searchButton: Locator;
    readonly searchResultCount: Locator;


    constructor(page: Page) {
        this.page = page;
        this.searchInput = page.locator('[data-test="search-query"]');
        this.searchButton = page.locator('[data-test="search-submit"]');
        this.searchResultCount =
            page.locator('[data-test="search-result-count"]');
    }

    async openHomePage(): Promise<void> {
        await this.page.goto('/');
    }

    async enterSearchQuery(query: string): Promise<void> {
        await this.searchInput.fill(query);
    }

    async clickSearch(): Promise<void> {
        await this.searchButton.click();
    }

    async searchForProduct(query: string): Promise<void> {
        await this.enterSearchQuery(query);
        await this.clickSearch();
    }
}   