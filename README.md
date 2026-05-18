# FMDB Web

WordPress site for the Federación Mexicana de Dodgeball (FMDB). The legacy
React + Vite site has been archived in the
[`legacy`](https://github.com/yayodelariva/fmdbweb/tree/legacy) branch.

## Local development

Requires Docker + Docker Compose.

```bash
cp .env.example .env       # fill in MYSQL_* values (defaults work for local dev)
docker compose up -d
```

| Service   | URL                          |
|-----------|------------------------------|
| Site      | http://localhost:8080        |
| wp-admin  | http://localhost:8080/wp-admin |
| MailHog   | http://localhost:8025        |

On first boot, complete the WordPress installer at `/wp-admin/install.php`,
then activate the **FMDB Theme** (under Appearance → Themes).

The parent theme is [Kadence](https://wordpress.org/themes/kadence/) v1.4.5
(install from WP admin or `wp theme install kadence --version=1.4.5`). It is
not tracked in this repo because no customizations were made — all theme
overrides live in the child theme.

## What's in the repo

Only custom code is tracked. WordPress core, third-party plugins, uploads,
and stock themes are managed by WordPress itself.

```
wp-content/
├── themes/fmdb-theme/        Custom Kadence child theme
│   ├── functions.php         Slim loader — requires every file under inc/
│   ├── inc/                  All theme logic, split by concern
│   ├── assets/               css/, js/, admin/, mexico-map.svg
│   └── page-*.php            Page templates (mi-equipo, equipos, eventos, …)
└── mu-plugins/
    ├── fmdb-login.php        Adds a "Regístrate aquí" link to the login screen
    └── fmdb-smtp.php         Routes wp_mail() through MailHog in dev
```

### `inc/` map

| File             | Purpose                                                      |
|------------------|--------------------------------------------------------------|
| `helpers.php`    | Shared helpers (`fmdb_mexican_states`, `fmdb_player_avatar`…) |
| `assets.php`     | Per-page conditional CSS/JS enqueuing with `filemtime()`     |
| `cpt.php`        | `fmdb_team`, `fmdb_league`, `fmdb_seleccion` post types      |
| `roles.php`      | `jugador` role, admin gating, admin-bar hiding               |
| `templates.php`  | `single-tribe_events.php` template override                  |
| `acf-fields.php` | ACF field groups + save/load hooks                           |
| `cmb2-fields.php`| Tournament bracket, team roster/results, event PDFs          |
| `events.php`     | Events Calendar customizations + admin event-type picker     |
| `woocommerce.php`| Theme support, Spanish strings, guest add-to-cart gating     |
| `nav.php`        | Primary-nav injection + profile dropdown                     |
| `login.php`      | Custom login/lostpassword URL filters                        |
| `shortcodes.php` | `[fmdb_map]`                                                 |
| `misc.php`       | Small filters (comments/pings off for posts)                 |

## Required plugins

Install via wp-admin:

- Advanced Custom Fields (ACF Free)
- CMB2
- The Events Calendar
- WooCommerce
- Members — provides the `editor_fmdb` and `representante_equipo` roles
  referenced by the theme
