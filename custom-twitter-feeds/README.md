# Custom Twitter Feeds - Developer Documentation

This is a WordPress plugin for displaying customizable Twitter/X feeds. This document provides technical information about the project structure and build process.

## Project Structure

```
custom-twitter-feeds/
├── admin/                          # Admin interface
│   ├── Traits/                     # Admin traits and mixins
│   ├── assets/                     # Admin assets
│   │   ├── css/                    # Admin stylesheets
│   │   ├── img/                    # Admin images
│   │   │   └── about/              # About page images
│   │   └── js/                     # Admin JavaScript
│   ├── builder/                    # Feed builder interface
│   │   ├── assets/                 # Builder assets
│   │   │   ├── css/                # Builder stylesheets
│   │   │   ├── img/                # Builder images
│   │   │   └── js/                 # Builder JavaScript
│   │   └── templates/              # Builder templates
│   │       ├── preview/            # Preview templates
│   │       ├── screens/            # Screen templates
│   │       └── sections/           # Section templates
│   │           ├── create-feed/    # Feed creation sections
│   │           ├── customizer/     # Customizer sections
│   │           ├── feeds/          # Feed management sections
│   │           └── popup/          # Popup templates
│   └── views/                      # Admin page views
│       ├── about/                  # About page
│       ├── sections/               # Shared sections
│       ├── settings/               # Settings pages
│       │   └── tab/                # Settings tabs
│       └── support/                # Support page
├── build/                          # Build configuration
│   ├── composer/                   # Composer build config
│   └── custom-twitter-feeds/       # Plugin build config
├── css/                            # Frontend stylesheets
├── img/                            # Frontend images
├── inc/                            # Core functionality
│   ├── Admin/                      # Admin classes
│   │   └── Traits/                 # Admin traits
│   ├── Builder/                    # Feed builder classes
│   │   ├── Controls/               # UI controls
│   │   └── Tabs/                   # Builder tabs
│   ├── Integrations/               # Third-party integrations
│   │   └── Analytics/              # Analytics integration
│   ├── SmashTwitter/               # Twitter API service
│   │   └── Services/               # Service classes
│   ├── V2/                         # Twitter API v2 adapters
│   └── blocks/                     # Gutenberg blocks
├── js/                             # Frontend JavaScript
├── languages/                      # Translation files
├── templates/                      # Frontend templates
├── views/                          # Frontend views
├── custom-twitter-feed.php         # Main plugin file
├── composer.json                   # Composer configuration
├── Makefile                        # Build commands
├── scoper.inc.php                  # PHP Scoper configuration
├── phpcs.xml                       # Code standards config
├── phpstan.neon                    # Static analysis config
├── README.txt                      # Plugin documentation
└── uninstall.php                   # Uninstall procedures
```

## Build Process

The project uses a sophisticated build system managed by Smash Bundler.

### Prerequisites

1. **PHP Scoper** - Must be installed globally:
   ```bash
   composer global require humbug/php-scoper
   ```

2. **Composer** - For dependency management

3. **Smash Bundler** - Available as dev dependency in composer.json

4. **WP-CLI** - For translation file generation (optional)

### Production Build

To create a production-ready zip file:

```bash
make package
```

This command uses the Smash Bundler with configuration from `build/custom-twitter-feeds/config.php` to:
- Install and scope PHP dependencies to prevent conflicts
- Copy all necessary plugin files
- Generate a versioned zip file (e.g., `custom-twitter-feeds-2.3.2.zip`)

### Other Build Commands

Available via Makefile:

1. **Development Setup:**
   ```bash
   composer install
   ```

3. **Translation Files:**
   ```bash
   make translations
   ```
   Generates and updates .pot, .po, and .mo files.

### Code Quality Tools

- `composer phpcs` - Run PHP CodeSniffer for code standards
- `composer phpcbf` - Auto-fix code standard violations
- PHPStan for static analysis

### Dependency Management

The build process uses PHP Scoper to isolate dependencies under the `Smashballoon\TwitterFeed\Vendor` namespace, preventing conflicts with other WordPress plugins that may use the same libraries.

## Core Components

### Main Plugin File
- `custom-twitter-feed.php` - Contains plugin initialization, constants definition, and basic setup

### Admin Interface
- Located in `admin/` directory
- Includes settings pages, feed builder, and management interfaces
- Assets are organized by functionality (CSS, JS, images)

### Core Functionality
- Located in `inc/` directory
- Contains all PHP classes and business logic
- Organized by feature area (Admin, Builder, Integrations, etc.)

### Frontend
- `css/` and `js/` - Frontend assets
- `templates/` - Display templates for feeds
- `views/` - Additional view files

### Build System
- `build/` - Contains build configuration files
- `Makefile` - Defines build commands
- `scoper.inc.php` - PHP Scoper configuration for dependency isolation