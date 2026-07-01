import { APIRequestContext, expect } from '@playwright/test';

export class UserApi {

    async createUser(
    request: APIRequestContext,
    user: any
): Promise<void> {

    const response = await request.post(
        'https://api.practicesoftwaretesting.com/users/register',
        {
            data: user
        }
    );

    expect(response.ok()).toBeTruthy();
}

async loginUser(
    request: APIRequestContext,
    user: any
) {

    return await request.post(
        'https://api.practicesoftwaretesting.com/users/login',
        {
            data: {
                email: user.email,
                password: user.password
            }
        }
    );
}
}