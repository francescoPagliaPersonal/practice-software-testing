import { APIRequestContext, expect } from '@playwright/test';

export class ProductApi {

    async getAllProducts(
        request: APIRequestContext
    ) {
        const response = await request.get(
            'https://api.practicesoftwaretesting.com/products'
        );
        expect(response.ok()).toBeTruthy();
        return await response.json();
    }

    async getFirstProduct(
        request: APIRequestContext
    ) {
        const response = await this.getAllProducts(request);
        return response.data[0];
    }
}
