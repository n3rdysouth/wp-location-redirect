# WP Location Redirect
WP Location Redirect is a powerful WordPress plugin that enables you to redirect users to a specific URL based on their geographical location. Whether it's by country, state/region, or city, this plugin utilizes the GeoIP2-City database to accurately determine where your visitors are coming from.

## Configuration
I have included a copy of a recent GeoIP2-City database. There is a button to download the latest version of the GeoIP2-City database in order to keep this up to date. For an easier download, we rely on getting this file from [https://cdn.jsdelivr.net/npm/geolite2-city/GeoLite2-City.mmdb.gz](https://cdn.jsdelivr.net/npm/geolite2-city/GeoLite2-City.mmdb.gz).

## Added Functions
This plugin adds the following functions that you can use in your PHP code:

### Save New Redirect Rule
```php
wp_location_redirect_save_rule( array(
    'country' => 'US',
    'state'   => 'FL',
    'city'    => 'Miami',
    'url'     => 'https://example.com/miami/'
) );
```
### Fetch All Rules

```php
$rules = wp_location_redirect_get_rules();
foreach ( $rules as $rule ) {
    echo "Rule {$rule->id}: Redirect users in {$rule->country}, {$rule->state}, {$rule->city} to {$rule->url}.<br>";
}
```

## Plugin Details

### Plugin Information
| **Field**          | **Value**          |
|--------------------|--------------------|
| **Requires at least**: | 5.0 |
| **Tested up to**:      | 6.4 |
| **Requires PHP**:      | 7.4 |
| **Stable tag**:        | 1.0.0 |
| **License**:           | GPLv2 or later |
| **License URL**:       | [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html) |

### Description
This plugin is released under the GPLv2 or later license. It allows you to freely use, distribute, and modify the plugin under the same license terms.

The full license can be viewed here: [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).