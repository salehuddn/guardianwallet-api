# Installation Guide

1. Run Composer to install dependencies:

    ```
    composer install
    ```

2. Generate a new application key:

    ```
    php artisan key:generate
    ```

3. Migrate the database:

    ```
    php artisan migrate
    ```

4. Copy the example environment file and configure it:
    ```
    cp .env.example .env
    ```

# API Usage

- For requests to endpoints under the `api/v1/secured` prefix, you need to include the token in the Authorization header using the Bearer authentication method.

- After logging in, the server will respond with a token. Copy this token.

- For every request to the secured API endpoints, include a header named `Authorization` with the value set to `Bearer YOUR_TOKEN`, where `YOUR_TOKEN` is the token received after login.

## Example

Here's how you can use the API with cURL:

```bash
curl -X GET http://127.0.0.1:8000/api/v1/secured/endpoint \
-H "Authorization: Bearer YOUR_TOKEN"

```
