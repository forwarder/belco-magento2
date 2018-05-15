# Belco extension for Magento 2

# Version 0.2.2

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
