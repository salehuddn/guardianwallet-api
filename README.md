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

1. Generate API Key:

    - Start your Laravel server:
        ```
        php artisan serve
        ```
    - Make a POST request to `http://127.0.0.1:8000/api/generateApiKey` to generate an API key.

2. Set API Key:

    - Once you receive the API key, paste it into the `.env` file under `API_KEY=`.

3. Using the API:
    - For every request to the API, include a header named `x-api-key` with the value set to the generated API key.

## Example

Here's how you can use the API with cURL:

```bash
curl -X GET http://127.0.0.1:8000/api/endpoint \
-H "x-api-key: YOUR_API_KEY"
```
