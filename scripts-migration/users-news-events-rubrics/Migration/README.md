The migration app
==

Goal
-

This migration app moves tela-botanica data from the old site (based on SPIP + bazar-fiche + tela annuaire) to the new one (based on WordPress/BuddyPress/WPML plugin). Those data include:

* users/user metas/user profiles/user activities
* news/news comments
* events/event metas
* news covers (news cover images)
* rubrics

Base namespaces
-

The migration app uses a small simple migration API which resides in the /Migration/Api namespace. The app, by itself, lives in the /Migration/App one.

Configuration
-

By convention, the API expects the following configuration files to be located in the /Migration/App/Config namespaces:

* datasources.php
* config.php

The former defines data sources which refer to the various (source and target) DB connnections used in the migration process. The latter defines various constants used during the migration:

* the WP installation folder path
* rsync related informations - for covers)
* the admin's email to send failure reports).

Before running a migration, one must make sure to have those two files created. Two template files - which can be copied, renamed and edited to fit the actual system configuration - can be found at:

* /Migration/App/Config/datasources.php-dist
* /Migration/App/Config/config.php-dist

The config files contain enum classes which MUST NOT be edited (as they are used extensively throughout the migration app code). Only the associative arrays defining config constants must be. Ideally those enum classes should be moved outside of the config files to make this clearer. Also, please, don't edit the array names.

Usage
-

For a given context, the app can be launched by issuing the following command:

python tb_migrate_site <context-name>

A context is an atomic set of related data. Contexts are data-wise independent one from another, except "covers" who depends on "news-events" (covers will not be imported without imported news). There are currently four of them, namely:

* "users"
* "news-events"
* "covers"
* "rubrics"
