=== All-in-One WP Migration and Backup ===
Contributors: yani.iliev, bangelov, pimjitsawang
Tags: backup, transfer, copy, move, clone
Requires at least: 3.3
Tested up to: 6.8
Requires PHP: 5.3
Stable tag: 7.93
License: GPLv3 or later

Trusted by 60M+ sites: The gold standard for WordPress migrations, backups, and site transfers since 2013.

== Description ==
**The Most Trusted WordPress Migration & Backup Solution Since 2013**

All-in-One WP Migration is the gold standard for WordPress site migration, used by over 60 million websites worldwide - from small blogs to Fortune 500 companies and government agencies. Our plugin offers enterprise-grade reliability with beginner-friendly simplicity.

**Why Choose All-in-One WP Migration?**

* **Effortless Migration**: Export your entire site with a single click - including database, media, themes, and plugins
* **Zero Downtime**: Complete your migration with no service interruptions
* **Universal Compatibility**: Works across all hosting providers, from budget shared hosting to high-end dedicated servers
* **Technical Excellence**: Engineered for reliability with memory-efficient processing (512KB chunks), ideal for resource-limited environments
* **No Technical Skills Required**: Intuitive interface designed for users of all skill levels
* **Cross-Database Support**: Seamlessly move between MySQL, MariaDB, and SQLite databases
* **Secure & Reliable**: Trusted by Boeing, NASA, Harvard, Stanford, Automattic, and government agencies worldwide

**How It Works - Simple as 1-2-3:**

1. **Install** the plugin on your source and destination sites
2. **Export** your site to a .wpress file with one click
3. **Import** using our drag-and-drop importer on your destination site

**For Developers & Power Users:**

* **Advanced Find & Replace**: Control exactly what changes during migration
* **Selective Content Migration**: Include/exclude specific content types
* **PHP 5.3-8.4 Compatibility**: Works across virtually all hosting environments
* **Custom WPress Format**: Our optimized archive format ensures data integrity
* **Hook System**: Extensive API for custom integration and workflows
* **Command-Line Support**: Automate migrations via WP-CLI

**Premium Extensions:**

Enhance your migration workflow with our [premium extensions](https://servmask.com/products) for:

* **Unlimited Site Size**: Migrate sites of any size
* **Cloud Storage Integration**: Direct migration to/from Dropbox, Google Drive, OneDrive, and more
* **Multisite Support**: Migrate complex WordPress networks
* **Scheduled Backups**: Automated, recurring backups
* **Database Filtering**: Exclude specific tables or data

**Features Spotlight:**

* WCAG 2.1 AA Level accessibility compliance
* Mobile-responsive interface
* [Browse WPRESS files online](https://traktor.servmask.com) or [extract locally](https://traktor.wp-migration.com)
* Automatic URL and path replacement
* WordPress Playground integration for SQLite/MySQL migration
* Regular bi-weekly updates ensuring compatibility with the latest WordPress versions
* Available in 50+ languages including Japanese

**Trusted by the Government and Big Corporations:**

Many enterprise customers, government organizations, and universities use, love, and trust All-in-One WP Migration. Here are some: Boeing, NASA, VW, IBM, Harvard University, Stanford University, Lego, P&G, Automattic, State of California, State of Hawaii.
This broad adoption and usage of All-in-One WP Migration demonstrate how **safe, reliable and adaptable** the plugin is for just about any website migration need.

**Update Frequency:**
Our team is dedicated to keeping All-in-One WP Migration up-to-date and secure. We release updates every two weeks or at least once a month, ensuring compatibility with the latest WordPress versions, including beta releases. Our proactive testing and feedback to the WordPress core team help in preemptively addressing any potential issues, providing our users with a reliable and forward-compatible migration and backup solution.

**Full Compatibility and Support:**

All-in-One WP Migration has been extensively tested and confirmed to be compatible with most WordPress plugins and themes.
This means you don't get to experience cross-plugin compatibility issues that can slow down, bug, or break down your WordPress website when you install and use All-in-One WP Migration.
As a matter of fact, All-in-One WP Migration has partnered with multiple theme/plugin vendors to distribute their themes/plugins with us as a single, easy to use, easy to install package.
These vendors trust us and our plugin to provide their customers with reliable product delivery, support, migrations, and backups.

**Cloud Storage Supported:**

All-in-One WP Migration supports and syncs seamlessly with top cloud storage services.
The plugin comes preinstalled on all Bitnami WordPress sites running on AWS, Google Compute Cloud, and Microsoft Azure.

**Case Studies:**

* Small Business Growth: A small online retailer was able to seamlessly migrate to a more robust hosting solution to handle increasing traffic during peak shopping seasons, ensuring smooth customer experiences without downtime.
* Educational Institutions: A prominent university utilized All-in-One WP Migration to consolidate multiple departmental sites into a single, unified WordPress network, simplifying management and enhancing site security.
* Government Reliability: Following a directive to improve digital accessibility, a government agency used our plugin to migrate their content to a new, compliant platform without impacting public access to critical information.

= Contact us =
* [Report a security vulnerability](https://patchstack.com/database/vdp/all-in-one-wp-migration)
* [Get free help from us here](https://servmask.com/help)
* [Report a bug or request a feature](https://servmask.com/help)
* [Find out more about us](https://servmask.com)

[youtube http://www.youtube.com/watch?v=BpWxCeUWBOk]

[youtube http://www.youtube.com/watch?v=mRp7qTFYKgs]

== Installation ==
1. All-in-One WP Migration can be installed directly through your WordPress Plugins dashboard.
1. Click "Add New" and Search for "All-in-One WP Migration"
1. Install and Activate

Alternatively, you can download the plugin using the download button on this page and then upload the all-in-one-wp-migration folder to the /wp-content/plugins/ directory then activate throught the Plugins dashboard in WordPress

== Screenshots ==
1. Mobile Export page
2. Mobile Import page
3. Plugin Menu

== Privacy Policy ==
All-in-One WP Migration is designed to fully respect and protect the personal information of its users. It asks for your consent to collect the user's email address when filling the plugin's contact form.
All-in-One WP Migration is in full compliance with General Data Protection Regulation (GDPR).
See our [GDPR Compliant Privacy Policy here](https://www.iubenda.com/privacy-policy/946881).

== Changelog ==
= 7.93 =
**Fixed**

* Compatibility issue with PHP 7 and PHP 5 due to trailing comma in style registration

= 7.92 =
**Improved**

* Passed Plugin Check Plugin (PCP) validation
* Archive name generation

= 7.91 =
**Added**

* CiviCRM for WordPress support

= 7.90 =
**Added**

* Introduced a constant to disable MySQL late row lookups for enhanced database performance

**Improved**

* Enhanced SQLite database integration for improved stability and efficiency
* Strengthened serialization replacement mechanism to address an unauthenticated PHP Object Injection vulnerability (CVE-2024-10942). Special thanks to Webbernaut for responsibly disclosing this issue
* Preserved the wp_rocket_settings option during exports for improved user experience

**Fixed**

* Resolved PHP 8.4 deprecation warnings

= 7.89 =
**Improved**

* Upgraded to Node.js 22 for better performance and security
* Updated all plugin dependencies to keep things running smoothly and securely

= 7.88 =
**Fixed**

* Fixed an issue where the upload progress was stuck at 100%
* Fixed an issue where the upload could not be cancelled before it was completed

**Improved**

* Improved user-facing messages to be friendlier, direct, consistent, and more informative.

= 7.87 =
**Fixed**

* Resolved a vulnerability where error logs were publicly accessible with a known name by appending random affixes to error log filenames, making them unguessable. Error logs are now automatically deleted daily and during plugin updates. Special thanks to villu164 for responsibly disclosing this issue.
* Resolved a vulnerability where an administrator user could inject arbitrary PHP code through specific inputs. This vulnerability requires administrator-level access to exploit, ensuring that unauthorized users cannot perform this action. Special thanks to Ryan Kozak for responsibly disclosing this issue.

= 7.86 =
**Fixed**

* Resolved an issue with PHP 8.4 compatibility and restoring backup files via WP-CLI

= 7.85 =
**Added**

* PHP 8.4 compatibility

= 7.84 =
**Added**

* New hooks during the export and import processes to allow for custom actions and integrations

= 7.83 =
**Fixed**

* Resolved an issue where downloading backup files was failing on WordPress Playground environments

= 7.82 =
**Added**

* SQLite support
* WordPress Playground support

= 7.81 =
**Added**

* Reset Hub Page: Introducing a new reset hub page, providing users with powerful reset tools for efficient site management. This feature allows for easier resets of WordPress environments, facilitating smoother development and testing workflows.

**Improved**

* Better W3TC Support
* PHP Compatibility Checks: Display a warning notification, when you move/restore your site to a different PHP version.

= 7.80 =
**Added**

* Support for update-services plugin
* Domain name conversion to dashes from dots in the backup name for improved hosting providers compatibility

**Improved**

* Better support for Multisite to Standalone and Standalone to Multisite exports and imports, streamlining the migration process

= 7.79 =
**Added**

* Support for WordPress v6.4

= 7.78 =
**Added**

* Implemented a new Schedules page within the plugin, displaying various advanced features exclusive to premium extensions

= 7.77 =
**Added**

* Tested the new version of WordPress 6.3

= 7.76 =
**Fixed**

* Removed the [beta] label from advanced settings
