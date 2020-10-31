# Billplz for Formidable Forms

Accept payment using Billplz by using this plugin.

## Installation

### Manual Installation

* Download: [Plugin File](https://github.com/billplz/billplz-for-formidable/archive/master.zip)
* Login to WordPress Dashboard
* Navigate to Plugins >> Add New >> Upload
* Upload the files >> Activate

## Configuration

* Login to WordPress Dashboard
* Navigate to Formidable >> Forms >> Settings >> Form Actions >> Billplz
* Set up API Secret Key, Collection ID and X Signature Key and ALL relevant details
* Save changes

### Configuration Issues

* Every configuration per form will create a record in `posts` table.
* The **post id** will be stored as ___action_id___ in the same record.
* Due to the way Formidable Forms store the **action_id** that will always **0** upon first save, you need to edit some of the information and update again (to make it become non-zero).
* This is a bug on Formidable Forms where it is not possible to be solved from this plugin itself.

## Other

Facebook: [Billplz Dev Jam](https://www.facebook.com/groups/billplzdevjam/)