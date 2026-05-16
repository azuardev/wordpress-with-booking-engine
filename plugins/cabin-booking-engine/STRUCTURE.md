# Hotel Booking Engine - Plugin Structure

## 📁 Folder Organization

```
cabin-booking-engine/
├── cabin-booking-engine.php         # Plugin bootstrap/entry point
├── README.txt                        # Plugin description
│
├── includes/                         # Plugin logic (best practice: separate from web root ideally)
│   ├── class-plugin.php            # Main plugin class (orchestrates everything)
│   │
│   ├── core/                        # Core functionality
│   │   ├── class-post-types.php     # CPT registration (cabin, cbe_stay_page)
│   │   ├── class-bookings.php       # Booking management & database
│   │   └── class-database.php       # Database schema & migrations
│   │
│   ├── admin/                       # WordPress admin functionality
│   │   ├── class-admin.php          # Admin page orchestrator
│   │   ├── class-stay-pages-form.php   # Stay Pages form & management
│   │   ├── class-rooms-form.php     # Rooms (Cabin) form & management
│   │   └── class-settings.php       # Plugin settings
│   │
│   ├── frontend/                    # Frontend (public-facing) functionality
│   │   ├── class-frontend.php       # Frontend orchestrator
│   │   ├── class-stay-pages.php     # Stay Page rendering
│   │   ├── class-shortcodes.php     # Shortcode handlers
│   │   └── class-single-cabin.php   # Single cabin page rendering
│   │
│   └── helpers/
│       └── functions.php            # Utility/helper functions
│
├── assets/                          # Frontend & admin assets
│   ├── css/
│   │   ├── cbe.css                 # Frontend styles
│   │   └── cbe-admin.css           # Admin panel styles
│   │
│   └── js/
│       ├── cbe.js                  # Frontend interactions (modals, image viewer, etc)
│       └── cbe-admin.js            # Admin panel interactions
│
├── templates/                       # Template files
│   └── stay-page.php               # Virtual stay page template
│
└── languages/                       # Translation files
    └── cabin-booking-engine.pot     # Translatable strings
```

## 🏗️ Architecture

### Main Entry Point

- **cabin-booking-engine.php** - Bootstrap file that:
  - Defines plugin metadata
  - Loads includes/class-plugin.php
  - Instantiates the main plugin class
  - Minimal logic - acts as orchestrator

### Class Organization

#### `Cabin_Booking_Engine` (Main Class)

- Initializes all sub-modules
- Manages plugin lifecycle (activation, deactivation)
- Coordinates between admin and frontend

#### Core Module (`includes/core/`)

- **Post Types**: Register 'cabin' and 'cbe_stay_page' custom post types
- **Bookings**: Handle booking submissions, validation, notifications
- **Database**: Schema creation and updates

#### Admin Module (`includes/admin/`)

- **Stay Pages Form**: Create/edit stay pages with room assignment
- **Rooms Form**: Create/edit rooms (cabin post type)
- **Settings**: Plugin configuration and payment methods

#### Frontend Module (`includes/frontend/`)

- **Stay Pages**: Render stay page with room cards and modals
- **Shortcodes**: Handle various shortcodes
- **Single Cabin**: Single room detail pages

#### Helpers (`includes/helpers/`)

- Utility functions used across modules
- Database helpers
- Validation functions

## 📋 Key Classes

### Core Classes

- `CBE_Post_Types` - Register CPTs and taxonomies
- `CBE_Bookings` - Booking form, validation, submission
- `CBE_Database` - Database table management

### Admin Classes

- `CBE_Admin` - Admin menu and pages
- `CBE_Stay_Pages_Form` - Stay page admin interface
- `CBE_Rooms_Form` - Room admin interface
- `CBE_Settings` - Plugin settings

### Frontend Classes

- `CBE_Frontend` - Frontend orchestrator
- `CBE_Stay_Pages` - Stay page rendering
- `CBE_Shortcodes` - Shortcode registration

## 🔄 Dependencies Flow

```
cabin-booking-engine.php (bootstrap)
    ↓
Cabin_Booking_Engine (main class)
    ├── Core modules (post types, database, bookings)
    ├── Admin modules (forms, settings)
    ├── Frontend modules (rendering, shortcodes)
    └── Helpers (shared utilities)
```

## 💾 Database

- **Table**: `wp_cbe_bookings`
- Managed by `CBE_Database` class
- Schema migrations in `class-database.php`

## 🎨 Assets Loading

- Admin assets: Enqueued in admin pages only
- Frontend assets: Enqueued on frontend globally
- Lazy loading for image viewers
- Conditional loading for modals

## 📝 Naming Conventions

- **Classes**: `CBE_Module_Name` or `Cabin_Booking_Engine` for main
- **Functions**: `cbe_function_name()`
- **Hooks**: `cbe_hook_name`
- **CSS classes**: `.cbe-element-name`
- **JS selectors**: `[data-cbe-attribute]`

## ✅ Best Practices Implemented

1. **Single Responsibility** - Each class has one clear purpose
2. **DRY** - Reusable helper functions in `helpers/functions.php`
3. **Namespace separation** - Core, Admin, Frontend kept separate
4. **Asset management** - CSS/JS organized by use case
5. **Template files** - Separated from PHP logic
6. **Localization ready** - All strings use `__()` and `esc_*()` functions
