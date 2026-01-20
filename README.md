# TechnicallyEmployableProject
Repository for the team project of the module Team Project with code COM2020. 

# Project Structure

Publicly accessible PHP templates — i.e. pages (e.g., home, login, dashboard) — are to be stored in the root directory.

As in MVC, views (the frontend) interface with controllers (which contain business logic), which then manipulate models (representations of entities — e.g., User).

- **/assets** — to store frontend assets, excl. any PHP helper functions or templates/partials.
  - **/css** — to store a single, global CSS file and corresponding source map.
  - **/scss** — to store SCSS (Sass) stylesheets (compiled to CSS). Structured according to popular industry practice.
    - **/abstracts** — to store config file and any mixins.
    - **/base** — to store base styles affecting *everything* (e.g., global typographical styles).
    - **/layout** — to store styles for reoccurring/global major layout elements (e.g., a dashboard sidebar, page footer, etc.).
    - **/components** — to store styles for reusable atomic components (e.g., buttons, textboxes, etc.).
    - **/pages**— to store styles unique to individual pages.
  - **/js** — to store JavaScript source code.
  - **/img** — to store images and icons.

- **/backend** — to store backend source code
  - **/controller** — to store 'controller' source code.
  - **/model** — to store 'model' source code.
  - **/global** — to store any global classes and functions, incl. helper functions.
- **/partials** — to store template partials — i.e. PHP templates included in other templates (e.g., page header, page footer, sidebar, etc.).