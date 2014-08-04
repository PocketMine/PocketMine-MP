#!/bin/bash
DIR="$(cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd)"
cd "$DIR"
while [ true ]
do
./bin/php5/bin/php -d enable_dl=On Steadfast.php $@
echo "Restarting the server..."
done