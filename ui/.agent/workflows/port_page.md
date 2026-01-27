---
description: Port a page from the legacy Plearnd project to Nuxni
---

# Port Page Workflow

1.  **Analyze Legacy Page**
    -   Locate the Vue file in `c:\wamp64\www\plearnd\resources\js\Pages`.
    -   Read the file to understand its structure, props, and child components.
    -   Identify any dependencies (libraries, mixins, etc.).

2.  **Identify Backend Logic**
    -   Search `c:\wamp64\www\plearnd\routes\web.php` for the route that serves this page.
    -   Identify the Controller and Method used (e.g., `WelcomeController@index`).
    -   Read the Controller logic to understand data fetching and business logic.

3.  **Port Backend (Laravel)**
    -   Create or update the corresponding Controller in `nuxni` (`c:\wamp64\www\nuxni\api\nuxniravel\app\Http\Controllers\Api`).
    -   Ensure necessary Eloquent Models and API Resources exist.
    -   Define a new API route in `c:\wamp64\www\nuxni\api\nuxniravel\routes\api.php`.

4.  **Port Frontend (Nuxt 3)**
    -   Create a new page in `c:\wamp64\www\nuxni\ui\pages`.
    -   Copy the template and logic from the legacy page.
    -   **Adaptations**:
        -   Replace Inertia's `<Link>` with `<NuxtLink>`.
        -   Replace `usePage().props` with `useFetch` to call the new API.
        -   Replace `ziggy` route helpers with hardcoded paths or Nuxt routing.
        -   Ensure Tailwind classes are compatible.
    -   Port any missing child components to `c:\wamp64\www\nuxni\ui\components`.

5.  **Verify**
    -   Test the API endpoint using Postman or browser.
    -   Test the frontend page in the browser.
    -   Check for console errors and missing assets.
