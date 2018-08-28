# Billplz for Formidable Forms

Accept payment using Billplz by using this plugin.

## Installation

### Manual Installation

* Download: [Plugin File](https://github.com/billplz/billplz-for-formidable/archive/master.zip)
* Rename the folder inside the archive to `formidable-billplz`
* Login to WordPress Dashboard
* Navigate to Plugins >> Add New >> Upload
* Upload the files >> Activate

## Configuration

* Login to WordPress Dashboard
* Navigate to Formidable >> Forms >> Settings >> Form Actions >> Billplz
* Set up API Secret Key, Collection ID and X Signature Key and ALL relevant details
* Save changes

## Development/Testing Environment

Set define on `wp-config.php` to bypass callback only function

```php
define('BILLPLZ', 'dev');
```

## Other

Facebook: [Billplz Dev Jam](https://www.facebook.com/groups/billplzdevjam/)