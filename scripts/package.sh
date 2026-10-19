#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
php -l appartment-dashboard/appartment-dashboard.php
php -l appartment-dashboard/includes/class-github-updater.php
for scenario in plain sitekit woocommerce both denied sitekit-unconfigured network; do
    php tests/dashboard.php "$scenario"
done
php tests/updater.php
rm -f appartment-dashboard.zip
zip -rq appartment-dashboard.zip appartment-dashboard
