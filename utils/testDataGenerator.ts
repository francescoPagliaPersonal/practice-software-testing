export function generateUniqueEmail(): string {
    const timestamp = Date.now();
    return `user_${timestamp}@example.com`;
}