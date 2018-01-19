# Belco extension for Magento 2

# Version 0.1.0

## Installation

```bash
composer require forwarder/belco-magento2:0.1.0
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

If composer throws an error, add this to your composer.json file
```json
    "repositories": [
        {     
            "url":"https://github.com/forwarder/belco-magento2.git",
            "type": "git"
        }
    ]
```
