#!/bin/bash
echo "==================================="
echo "Steadfast Installer"
echo "By williamtdr - github.com/williamtdr"
echo "==================================="
echo "["$(date +%k:%M)"] Installing to steadfast/."
mkdir steadfast/
cd steadfast/
echo "["$(date +%k:%M)"] Downloading Steadfast..."
wget https://github.com/SteadfastMC/Steadfast/archive/master.zip > /dev/null
echo "["$(date +%k:%M)"] Extracting..."
unzip master.zip > /dev/null
cd Steadfast-master
mv * ../
cd ../
rm -r Steadfast-master
rm master.zip
echo "["$(date +%k:%M)"] Downloading PHP..."
wget http://jenkins.pocketmine.net/view/PHP/job/PHP-PocketMine-Linux/lastSuccessfulBuild/artifact/archive/linux/64bit/PHP_5.5.15_x86-64_Linux.tar.gz > /dev/null
tar -xvf PHP*
rm PHP*
if [ $(./bin/php5/bin/php -r 'echo "yes";' 2>/dev/null) == "yes" ]; then
    OPCACHE_PATH="$(find $(pwd) -name opcache.so)"
    XDEBUG_PATH="$(find $(pwd) -name xdebug.so)"
    echo "" > "./bin/php5/bin/php.ini"
    #UOPZ_PATH="$(find $(pwd) -name uopz.so)"
    #echo "zend_extension=\"$UOPZ_PATH\"" >> "./bin/php5/bin/php.ini"
    echo "zend_extension=\"$OPCACHE_PATH\"" >> "./bin/php5/bin/php.ini"
    echo "zend_extension=\"$XDEBUG_PATH\"" >> "./bin/php5/bin/php.ini"
    echo "opcache.enable=1" >> "./bin/php5/bin/php.ini"
    echo "opcache.enable_cli=1" >> "./bin/php5/bin/php.ini"
    echo "opcache.save_comments=0" >> "./bin/php5/bin/php.ini"
    echo "opcache.fast_shutdown=1" >> "./bin/php5/bin/php.ini"
    echo "opcache.max_accelerated_files=4096" >> "./bin/php5/bin/php.ini"
    echo "opcache.interned_strings_buffer=8" >> "./bin/php5/bin/php.ini"
    echo "opcache.memory_consumption=128" >> "./bin/php5/bin/php.ini"
    echo "opcache.optimization_level=0xffffffff" >> "./bin/php5/bin/php.ini"
    echo "date.timezone=$TIMEZONE" >> "./bin/php5/bin/php.ini"
    echo "short_open_tag=0" >> "./bin/php5/bin/php.ini"
    echo "asp_tags=0" >> "./bin/php5/bin/php.ini"
    echo "phar.readonly=0" >> "./bin/php5/bin/php.ini"
    echo "phar.require_hash=1" >> "./bin/php5/bin/php.ini"
    echo " done"
    alldone=yes
else
    echo " invalid build detected"
fi
echo "Done, starting..."
./bin/php5/bin/php src/Pocketmine-MP.php