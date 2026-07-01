import { Page, Locator } from '@playwright/test';

export class RegistrationPage {
    readonly page: Page;
    readonly firstNameInput: Locator;
    readonly lastNameInput: Locator;
    readonly dobInput: Locator;
    readonly countryInput: Locator;
    readonly postalCodeInput: Locator;
    readonly houseNumberInput: Locator;
    readonly streetInput: Locator;
    readonly cityInput: Locator;
    readonly stateInput: Locator;
    readonly phoneInput: Locator;
    readonly emailInput: Locator;
    readonly passwordInput: Locator;
    readonly registerButton: Locator;
    //readonly emailErrorMessage: Locator;
    //readonly passwordErrorMessage: Locator;
    

    constructor(page: Page) {
        this.page = page;
        this.firstNameInput = page.locator('[data-test="first-name"]');
        this.lastNameInput = page.locator('[data-test="last-name"]');
        this.dobInput = page.locator('[data-test="dob"]');
        this.countryInput = page.locator('[data-test="country"]');
        this.postalCodeInput = page.locator('[data-test="postal_code"]');
        this.houseNumberInput = page.locator('[data-test="house_number"]');
        this.streetInput = page.locator('[data-test="street"]');
        this.cityInput = page.locator('[data-test="city"]');
        this.stateInput = page.locator('[data-test="state"]');
        this.phoneInput = page.locator('[data-test="phone"]');
        this.emailInput = page.locator('[data-test="email"]');
        this.passwordInput = page.locator('[data-test="password"]');
        this.registerButton = page.locator('[data-test="register-submit"]');
        //this.errorMessage = page.locator('[data-test="register-error"]');
    }

    async openRegistrationPage(): Promise<void> {
        await this.page.goto('/auth/register');
    }
    
    async enterFirstName(firstName: string): Promise<void> {
        await this.firstNameInput.fill(firstName);
    }

    async enterLastName(lastName: string): Promise<void> {
        await this.lastNameInput.fill(lastName);
    }

    async enterDOB(dob: string): Promise<void> {
        await this.dobInput.fill(dob);
    }

    async selectCountry(country: string): Promise<void> {
        await this.countryInput.selectOption(country);
    }

    async enterPostalCode(postalCode: string): Promise<void> {
        await this.postalCodeInput.fill(postalCode);
    }

    async enterHouseNumber(houseNumber: string): Promise<void> {
        await this.houseNumberInput.fill(houseNumber);
    }

    async enterStreet(street: string): Promise<void> {
        await this.streetInput.fill(street);
    }

    async enterCity(city: string): Promise<void> {
        await this.cityInput.fill(city);
    }

    async enterState(state: string): Promise<void> {
        await this.stateInput.fill(state);
    }

    async enterPhone(phone: string): Promise<void> {
        await this.phoneInput.fill(phone);
    }

    async enterEmail(email: string): Promise<void> {
        await this.emailInput.fill(email);
    }

    async enterPassword(password: string): Promise<void> {
        await this.passwordInput.fill(password);
    }

    async clickRegister(): Promise<void> {
        await this.registerButton.click();
    }

    async registerUser(firstName: string, lastName: string, dob: string, country: string, postalCode: string, houseNumber: string, street: string, city: string, state: string, phone: string, email: string, password: string): Promise<void> {
        await this.enterFirstName(firstName);
        await this.enterLastName(lastName);
        await this.enterDOB(dob);
        await this.selectCountry(country);
        await this.enterPostalCode(postalCode);
        await this.enterHouseNumber(houseNumber);
        await this.enterStreet(street);
        await this.enterCity(city);
        await this.enterState(state);
        await this.enterPhone(phone);
        await this.enterEmail(email);
        await this.enterPassword(password);
        await this.clickRegister();
    }
}