# WP Location Redirect
WP Location Redirect is a powerful WordPress plugin that enables you to redirect users to a specific URL based on their geographical location. Whether it's by country, state/region, city, or even zip code, this plugin utilizes the GeoIP2-City database to accurately determine where your visitors are coming from.

## Configuration
After installing this plugin, you will be prompted to download the latest version of the GeoIP2-City database. For an easier download, we rely on getting this file from https://cdn.jsdelivr.net/npm/geolite2-city/GeoLite2-City.mmdb.gz

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