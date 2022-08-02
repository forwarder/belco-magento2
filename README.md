# Belco extension for Magento 2

## Installation

```bash
composer require forwarder/belco-magento2
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

If composer throws an error, run this command
```bash
composer config repositories.belco git https://github.com/forwarder/belco-magento2.git
```

or add this to your composer.json file manually
```json
    "repositories": [
        "belco": {     
            "url":"https://github.com/forwarder/belco-magento2.git",
            "type": "git"
        }
    ]
```

## Configuration
After installing do the following procedure:
1. Go to the admin panel
2. Navigate to Stores >  Configuration.
3. Navigate to Belco > Settings.
4. Fill in the `Shop id` and `API Secret` and save.

`Shop id` and `API Secret` can be found within the belco app.
Under: Settings > API keys
