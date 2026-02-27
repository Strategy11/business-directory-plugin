# Business Directory Plugin

WordPress plugin that lets site owners run a free or paid business directory. The main plugin file is `business-directory-plugin.php`, and the internal prefix is `WPBDP` (classes) / `wpbdp` (functions).

## Tech Stack

- **PHP 7.4+** (platform target set in `composer.json`)
- **WordPress 5.8+** with standard plugin architecture
- **JavaScript**: ES6, React (via `@wordpress` packages), jQuery (legacy)
- **CSS**: LESS stylesheets compiled with Grunt
- **Build**: Webpack for JS bundles, Grunt for LESS/CSS and i18n

## Project Structure

```
business-directory-plugin.php   Main plugin bootstrap
includes/                       Core PHP classes and logic
  admin/                        WP Admin screens and settings
  controllers/                  Front-end controllers (views)
  fields/                       Custom form field types
  gateways/                     Payment gateway integrations
  helpers/                      Utility/helper classes
  models/                       Data models
  views/                        Admin view templates
  compatibility/                Compat layers for older versions
  db/                           Database operations
templates/                      Front-end template files (.tpl.php)
assets/
  css/                          Compiled stylesheets
  js/                           JavaScript files
  images/                       Image assets
themes/                         Directory display themes
languages/                      Translation files (.po/.mo)
tests/                          Codeception test suites
vendor/                         Composer dependencies (do not edit)
vendors/                        Bundled third-party libraries (do not edit)
node_modules/                   NPM dependencies (do not edit)
```

## Coding Standards

### PHP

Follow **WordPress coding standards** as configured in `phpcs.xml`:

- Tabs for indentation (not spaces).
- The text domain is `business-directory-plugin`.
- Every PHP function, method, and WordPress hook/filter callback must have a PHPDoc comment that includes an `@since x.x` tag. Always use `x.x` as the version -- it will be replaced by release automation. This applies to both new and modified functions.
- Use `use` statements for class imports at the top of the file.
- Prefer null coalescing (`??`) over ternary for default values. Never stack ternaries or null coalescing operators.
- Maximum function length: 100 lines. Maximum cognitive complexity: 10 (error) / 32 (warning).
- Line length limit: 250 characters (excludes tests and templates).
- Escape all output with appropriate WordPress functions (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`, etc.).
- Custom auto-escaped functions: `wpbdp_sanitize_value`, `wpbdp_get_server_value`, `wpbdp_render_page`, `wpbdp_admin_footer`, `wpbdp_render`, `wpbdp_render_msg`, `wp_nonce_url`.

### JavaScript

Follow **WordPress ESLint configuration** as defined in `.eslintrc`:

- ES6 syntax (always use `const`/`let`, never `var`).
- `@wordpress/eslint-plugin` recommended rules.
- JSDoc comments required.
- Text domain: `business-directory-plugin`.

### CSS/LESS

Stylelint with `stylelint-config-recommended-less`. Source files are in LESS format.

## Linting Commands

```bash
# PHP
composer phpcs          # Run PHPCS
composer phpcbf         # Auto-fix PHPCS violations
composer phpcsfixer     # Run PHP CS Fixer (dry-run)
composer phpstan        # Run PHPStan static analysis

# JavaScript
npm run lint:js         # ESLint
npm run format:js       # ESLint auto-fix

# CSS
npm run lint:css        # Stylelint
npm run format:css      # Stylelint auto-fix

# All linters
npm run lint            # Run all linters in parallel
npm run format          # Auto-fix all linters in parallel
```

## Rules

- Never modify files in `vendor/`, `vendors/`, or `node_modules/`.
- Never modify migration files in `includes/admin/upgrades/migrations/`.
- Do not run build commands (`npm run build`, `grunt`, `webpack`) unless explicitly asked.
- Keep changes minimal and focused. Do not refactor unrelated code.
- Use descriptive names instead of comments. Only add comments for complex logic.
