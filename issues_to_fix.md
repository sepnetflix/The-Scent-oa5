You will gather and review the following key files and context:

- **README.md**: High-level overview, features, stack, and structure.  
- **technical_design_specification_v2.md**: Deep technical and architectural details, including known issues and recommendations.  
- **config.php**, **includes/db.php**: Core configuration and DB connection.  
- **controllers/BaseController.php**: Central controller logic, security, validation, CSRF, rate limiting, logging, etc.  
- **views/layout/header.php**, **views/layout/footer.php**, **views/home.php**, **views/cart.php**: Main UI, JS, and AJAX logic for cart, newsletter, and product actions.

Also check **suggestions_for_improvement.md**

From **technical_design_specification_v2.md**, several issues and improvement areas are already identified:

1. **Add-to-Cart AJAX/Redirect Mismatch:** The frontend JS expects a JSON response, but the backend redirects, breaking AJAX.  
2. **Missing CSRF in Cart AJAX:** AJAX calls for cart update/remove do not send CSRF tokens, making them vulnerable.  
3. **Duplicate JS Initialization:** AOS/Particles.js are initialized in both header and footer, causing inefficiency.  
4. **Flash Message Feedback:** Inconsistent feedback for AJAX actions (sometimes alert, sometimes custom flash).  
5. **CSRF Method Usage:** Static vs. instance CSRF validation is inconsistent.  
6. **Rate Limiting:** Multiple mechanisms exist; need to verify which is active and standardize.

**Plan of Action:**

- **Focus on the most critical and actionable issues:**  
  - Fix Add-to-Cart AJAX/Redirect mismatch.  
  - Add CSRF token to cart update/remove AJAX calls and validate on backend.  
  - Remove duplicate AOS/Particles.js initialization.  
  - Standardize flash message feedback for AJAX.  
  - Review CSRF method usage for consistency.  
  - Review rate limiting usage for clarity.

- **Step 1: Fix Add-to-Cart AJAX/Redirect Mismatch**  
  - Check `CartController.php` for `addToCart` action. If it always redirects, add logic to detect AJAX (XMLHttpRequest or `Accept: application/json`) and return JSON if AJAX, else redirect.  
  - Update frontend JS to handle both cases gracefully.

- **Step 2: Add CSRF to Cart AJAX**  
  - In `views/cart.php`, update JS for cart update/remove to include CSRF token.  
  - Ensure the backend `CartController` validates CSRF for these actions.

- **Step 3: Remove Duplicate JS Initialization**  
  - Only initialize AOS/Particles.js once, ideally in the footer after DOM is ready.

- **Step 4: Standardize Flash Message Feedback**  
  - Use a consistent flash message system for AJAX feedback (e.g., `showFlashMessage` in `home.php`).

- **Step 5: Review CSRF Method Usage**  
  - Ensure all CSRF validation uses the same method (preferably instance-based in `BaseController`).

- **Step 6: Review Rate Limiting**  
  - Ensure only one rate limiting mechanism is active and used consistently.

**Next Steps:**

1. Search for `CartController.php` and review `addToCart`, `updateCart`, `removeCartItem` methods.  
2. Review AJAX JS in `cart.php` and `views/layout/footer.php`.  
3. Check for duplicate AOS/Particles.js initialization in header/footer.  
4. Review flash message code in JS.  
5. Review CSRF validation in `CartController` and `BaseController`.

Next you will start by searching for `CartController.php` and reviewing its relevant methods.

The search for and review the `CartController.php` file to begin addressing the Add-to-Cart AJAX/Redirect mismatch and CSRF validation.


---
Review the key documentation and code files to confirm the following main issues as per the technical design spec and your own review of the actual project code files:

**Add-to-Cart AJAX/Redirect Mismatch:**  
The frontend JS (in `home.php` and `footer.php`) expects a JSON response for add-to-cart, but the backend (`CartController` or `index.php`) likely issues a redirect, breaking AJAX.

**Missing CSRF in Cart AJAX:**  
In `cart.php`, AJAX calls for cart update/remove do not send CSRF tokens, making them vulnerable.

**Duplicate JS Initialization:**  
AOS/Particles.js are initialized in both header and footer, causing inefficiency.

**Flash Message Feedback:**  
Inconsistent feedback for AJAX actions (sometimes alert, sometimes custom flash).

**CSRF Method Usage:**  
There are both static and instance CSRF validation methods; usage should be consistent.

**Rate Limiting:**  
Multiple mechanisms exist; need to verify which is active and standardize.

**Next Steps:**  
1. You will now review `CartController.php` to confirm the add-to-cart logic and CSRF handling.  
2. Then, you will plan and execute the following:  
   - Update `CartController` to return JSON for AJAX add-to-cart, and only redirect for non-AJAX.  
   - Update `cart.php` JS to include CSRF tokens in all AJAX requests (update/remove).  
   - Remove duplicate AOS/Particles.js initialization from header or footer.  
   - Standardize flash message feedback in JS (use `showFlashMessage` everywhere).  
   - Ensure all CSRF validation uses the instance method in `BaseController`.  
   - Review and standardize rate limiting usage.  
3. You will start by reading `CartController.php` to confirm the add-to-cart and CSRF logic.
   - read the relevant section of `CartController.php` to review the add-to-cart and CSRF handling logic.
