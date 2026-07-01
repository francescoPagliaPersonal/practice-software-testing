import { test, expect } from '@playwright/test';
import userData from '../test-data/user.json';
import { generateUniqueEmail } from '../utils/testDataGenerator';
import { RegistrationPage } from '../pages/RegistrationPage';
import { UserApi } from '../api/UserApi';


test('T1_registeration_validUser_userIsRegistered', async ({ page, request }) => {

    const registerPage = new RegistrationPage(page);

    await registerPage.openRegistrationPage();

    const user = {
        ...userData,
        email: generateUniqueEmail()
    };

    await registerPage.registerUser(user.first_name, user.last_name, user.dob, user.address.country, user.address.postal_code, user.address.house_number, user.address.street, user.address.city, user.address.state, user.phone, user.email, user.password);

    await expect(page).toHaveURL(/.*\/login$/);

    const userApi = new UserApi();
    const loginResponse = await userApi.loginUser(
        request,
        user
    );

    expect(loginResponse.status()).toBe(200);

    const responseBody = await loginResponse.json();

    expect(responseBody.access_token).toBeTruthy();
    expect(responseBody.token_type).toBe('bearer');
});