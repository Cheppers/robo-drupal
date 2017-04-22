#!/usr/bin/env bash

set -x
set -e

if [ ! -d "$HOME/.sassphp/.git" ]; then
    git clone \
        --recursive \
        --single-branch \
        --branch PR3 \
        https://github.com/absalomedia/sassphp.git \
        "$HOME/.sassphp"
fi

cd "$HOME/.sassphp"
php ./install.php
make install
iniDir=$(php -r '$f = explode(",\n", php_ini_scanned_files()); echo dirname(reset($f)), "\n";')
echo 'extension=sass.so' > "$iniDir/sass.ini"

cd "$TRAVIS_BUILD_DIR"
