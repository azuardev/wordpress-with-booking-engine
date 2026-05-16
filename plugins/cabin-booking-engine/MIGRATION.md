# Refactoring Migration Guide

## ✅ Completed Changes

### 1. **Folder Structure**

- ✅ Created `includes/` directory with submodules
- ✅ Created `includes/core/` for core functionality
- ✅ Created `includes/admin/` for admin functionality
- ✅ Created `includes/frontend/` for frontend functionality
- ✅ Created `includes/helpers/` for utility functions
- ✅ Created `assets/css/` subfolder
- ✅ Created `assets/js/` subfolder

### 2. **Asset Organization**

- ✅ Moved `cbe.css` to `assets/css/cbe.css`
- ✅ Moved `cbe.js` to `assets/js/cbe.js`
- ✅ Moved `cbe-admin.css` to `assets/css/cbe-admin.css`
- ✅ Renamed and moved `cbe-cabin-admin.js` to `assets/js/cbe-admin.js`
- ✅ Updated all asset paths in `cabin-booking-engine.php`

### 3. **Helper Functions**

- ✅ Created `includes/helpers/functions.php` with utility functions:
  - `cbe()` - Get plugin instance
  - `cbe_get_option()` - Get plugin settings
  - `cbe_update_option()` - Update plugin settings
  - `cbe_get_cabin_price()` - Get room price
  - `cbe_get_bookings_table()` - Get database table name

### 4. **Documentation**

- ✅ Created `STRUCTURE.md` - Folder structure documentation
- ✅ Created constants in `cabin-booking-engine.php`:
  - `CBE_PLUGIN_DIR` - Plugin directory path
  - `CBE_PLUGIN_URL` - Plugin URL
  - `CBE_PLUGIN_VERSION` - Plugin version
  - `CBE_INCLUDES_DIR` - Includes directory path

## 🔄 Next Steps (For Gradual Modularization)

### Phase 2: Core Module Extraction

Create `includes/core/class-post-types.php`:

- Move `register_cpt()` method
- Move CPT callback functions
- Separate post type registration logic

Create `includes/core/class-database.php`:

- Move `maybe_upgrade_schema()` method
- Move database table creation logic
- Move migration functions

Create `includes/core/class-bookings.php`:

- Move `handle_booking_submission()` method
- Move `render_booking_form_shortcode()` method
- Move booking validation logic

### Phase 3: Admin Module Extraction

Create `includes/admin/class-admin.php`:

- Move admin menu registration
- Move admin page rendering
- Orchestrate admin submodules

Create `includes/admin/class-stay-pages-form.php`:

- Move stay pages form methods
- Move stay page meta box handling

Create `includes/admin/class-rooms-form.php`:

- Move rooms/cabin form methods
- Move cabin meta box handling

### Phase 4: Frontend Module Extraction

Create `includes/frontend/class-frontend.php`:

- Move frontend orchestration logic

Create `includes/frontend/class-stay-pages.php`:

- Move `render_custom_stay_page_body()`
- Move stay page rendering methods

Create `includes/frontend/class-shortcodes.php`:

- Move `register_shortcodes()`
- Move shortcode handlers

## 📁 Current File Structure

```
cabin-booking-engine/
├── cabin-booking-engine.php         ✅ Updated (paths + constants)
├── STRUCTURE.md                     ✅ Created (documentation)
├── includes/                        ✅ Created
│   ├── helpers/
│   │   └── functions.php            ✅ Created
│   ├── core/                        📁 Created (ready for modules)
│   ├── admin/                       📁 Created (ready for modules)
│   └── frontend/                    📁 Created (ready for modules)
├── assets/
│   ├── css/                         ✅ Created
│   │   ├── cbe.css                  ✅ Copied
│   │   └── cbe-admin.css            ✅ Copied
│   ├── js/                          ✅ Created
│   │   ├── cbe.js                   ✅ Copied
│   │   └── cbe-admin.js             ✅ Copied (renamed from cbe-cabin-admin.js)
│   ├── cbe.css                      ⚠️  Keep for backwards compatibility
│   ├── cbe.js                       ⚠️  Keep for backwards compatibility
│   ├── cbe-admin.css                ⚠️  Keep for backwards compatibility
│   └── cbe-cabin-admin.js           ⚠️  Can be removed after verification
└── templates/
    └── stay-page.php                ✅ (no changes needed)
```

## ⚠️ Backwards Compatibility

**Old file paths still exist in `assets/` root folder for backwards compatibility.**

- They are duplicates of the new organized files
- Once verified that all references work, they can be safely removed
- Keep them for now in case of custom integrations

## 🔧 How to Use New Structure

### Import helper functions:

```php
// In any part of the plugin or theme
$plugin = cbe(); // Get plugin instance
$price = cbe_get_cabin_price(123); // Get room price
$option = cbe_get_option('setting_key'); // Get plugin option
```

### Access plugin paths:

```php
echo CBE_PLUGIN_DIR; // /path/to/cabin-booking-engine/
echo CBE_PLUGIN_URL; // http://site.com/wp-content/plugins/cabin-booking-engine/
echo CBE_INCLUDES_DIR; // /path/to/cabin-booking-engine/includes/
```

## ✅ Testing Checklist

- [ ] Plugin loads without errors
- [ ] Frontend CSS/JS loads correctly
- [ ] Admin CSS/JS loads correctly
- [ ] All shortcodes work
- [ ] Stay pages display correctly
- [ ] Booking form works
- [ ] Admin forms work
- [ ] No console errors

## 📋 Notes

- The main `cabin-booking-engine.php` file is still large but can be gradually refactored
- New helper functions are available globally
- Plugin constants are defined for easy path access
- Asset organization follows WordPress plugin best practices
- Documentation is in place for future module extraction
