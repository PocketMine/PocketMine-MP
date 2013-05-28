#!/bin/bash
COMPILER_VERSION="0.12"

PHP_VERSION="5.4.15"
ZEND_VM="GOTO"

#Uncomment the double commented lines to enable (semi-broken) libedit support for cross compiling
##NCURSES_VERSION="5.9"
##LIBEDIT_VERSION="0.3"
ZLIB_VERSION="1.2.8"
PTHREADS_VERSION="0b863ea34e1f5c0a0eef6d50a7cbca58d39435cc"
CURL_VERSION="curl-7_30_0"

if [ "$1" == "android" ]; then
    TOOLCHAIN_PREFIX="arm-none-linux-gnueabi"
elif [ "$1" == "rpi" ]; then
    TOOLCHAIN_PREFIX="arm-linux-gnueabihf"
    march="armv6zk"
    mcpu="arm1176jzf-s"
    mtune="arm1176jzf-s"
    CFLAGS="-mfloat-abi=hard -mfpu=vfp"
else
    echo "Please suuply a proper platform"
    exit 1
fi

export CC="$TOOLCHAIN_PREFIX-gcc"
CROSS_COMPILE_FLAGS="--host=$TOOLCHAIN_PREFIX"

echo "[PocketMine] PHP installer and compiler for Linux & Mac - v$COMPILER_VERSION"
DIR="$(pwd)"
date > "$DIR/install.log" 2>&1
uname -a >> "$DIR/install.log" 2>&1
echo "[INFO] Checking dependecies"
type make >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"make\""; read -p "Press [Enter] to continue..."; exit 1; }
type autoconf >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"autoconf\""; read -p "Press [Enter] to continue..."; exit 1; }
type automake >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"automake\""; read -p "Press [Enter] to continue..."; exit 1; }
type libtool >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"libtool\""; read -p "Press [Enter] to continue..."; exit 1; }
type $CC >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install the correct cross compiler \"$CC\" and add the directory to PATH"; read -p "Press [Enter] to continue..."; exit 1; }
type m4 >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"m4\""; read -p "Press [Enter] to continue..."; exit 1; }
type wget >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"wget\""; read -p "Press [Enter] to continue..."; exit 1; }
type sed >> "$DIR/install.log" 2>&1 || { echo >&2 "[ERROR] Please install \"sed\""; read -p "Press [Enter] to continue..."; exit 1; }

[ -z "$THREADS" ] && THREADS=1;
[ -z "$march" ] && march="native";
[ -z "$mcpu" ] && mcpu="native";
[ -z "$mtune" ] && mtune="native";
[ -z "$CFLAGS" ] && CFLAGS="";

$CC -O3 -march=$march -mcpu=$mcpu -mtune=$mtune -fno-gcse $CFLAGS -Q --help=target >> "$DIR/install.log" 2>&1
if [ $? -ne 0 ]; then
  $CC -O3 -fno-gcse $CFLAGS -Q --help=target >> "$DIR/install.log" 2>&1
	if [ $? -ne 0 ]; then
		export CFLAGS="-O3 -fno-gcse "
	else
		export CFLAGS="-O3 -fno-gcse $CFLAGS"
	fi
else
	export CFLAGS="-O3 -march=$march -mcpu=$mcpu -mtune=$mtune -fno-gcse $CFLAGS"
fi


rm -r -f install_data/ >> "$DIR/install.log" 2>&1
rm -r -f php5/ >> "$DIR/install.log" 2>&1
rm -r -f bin/ >> "$DIR/install.log" 2>&1
mkdir -m 0777 install_data >> "$DIR/install.log" 2>&1
mkdir -m 0777 php5 >> "$DIR/install.log" 2>&1
mkdir -m 0777 bin >> "$DIR/install.log" 2>&1
cd install_data
set -e

#PHP 5
echo -n "[PHP] downloading $PHP_VERSION..."
wget http://php.net/get/php-$PHP_VERSION.tar.gz/from/this/mirror -q -O - | tar -zx >> "$DIR/install.log" 2>&1
mv php-$PHP_VERSION php
echo " done!"

#ncurses
##echo -n "[ncurses] downloading $NCURSES_VERSION..."
##wget http://ftp.gnu.org/pub/gnu/ncurses/ncurses-$NCURSES_VERSION.tar.gz -q -O - | tar -zx  >> "$DIR/install.log" 2>&1
##mv ncurses-$NCURSES_VERSION ncurses
##echo -n " checking..."
##cd ncurses
##./configure --prefix="$DIR/install_data/php/ext/ncurses" \
##--enable-static --enable-termcap $CROSS_COMPILE_FLAGS  >> "$DIR/install.log" 2>&1
##echo -n " compiling..."
##make -j $THREADS  >> "$DIR/install.log" 2>&1
##echo -n " installing..."
##make install  >> "$DIR/install.log" 2>&1
##echo -n " cleaning..."
##cd ..
##rm -r -f ./ncurses
##echo " done!"

#libedit
##echo -n "[libedit] downloading $LIBEDIT_VERSION..."
##wget http://download.sourceforge.net/project/libedit/libedit/libedit-$LIBEDIT_VERSION/libedit-$LIBEDIT_VERSION.tar.gz -q -O - | tar -zx  >> "$DIR/install.log" 2>&1
##echo -n " checking..."
##cd libedit

##remove
##CFLAGS=$CFLAGS" -I$DIR/install_data/php/ext/ncurses/include/ncurses -I$DIR/install_data/php/ext/ncurses/include" ./configure --prefix="$DIR/install_data/php/ext/libedit" --enable-static $CROSS_COMPILE_FLAGS  >> "$DIR/install.log" 2>&1
##echo -n " compiling..."
##if make -j $THREADS >> "$DIR/install.log" 2>&1 ; then
##	echo -n " installing..."
##	make install  >> "$DIR/install.log" 2>&1
##	HAVE_LIBEDIT="--with-libedit=$DIR/install_data/php/ext/libedit"
##else
##	echo -n " disabling..."
	HAVE_LIBEDIT="--without-libedit"
##fi
##echo -n " cleaning..."
##cd ..
##rm -r -f ./libedit
##echo " done!"

#zlib
echo -n "[zlib] downloading $ZLIB_VERSION..."
wget http://zlib.net/zlib-$ZLIB_VERSION.tar.gz -q -O - | tar -zx >> "$DIR/install.log" 2>&1
mv zlib-$ZLIB_VERSION zlib
echo -n " checking..."
cd zlib
./configure --prefix="$DIR/install_data/php/ext/zlib" \
--static >> "$DIR/install.log" 2>&1
echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
echo -n " cleaning..."
cd ..
rm -r -f ./zlib
echo " done!"

#curl
echo -n "[cURL] downloading $CURL_VERSION..."
wget https://github.com/bagder/curl/archive/$CURL_VERSION.tar.gz --no-check-certificate -q -O - | tar -zx >> "$DIR/install.log" 2>&1
mv curl-$CURL_VERSION curl
echo -n " checking..."
cd curl
./buildconf >> "$DIR/install.log" 2>&1
./configure --enable-ipv6 \
--disable-dict \
--disable-file \
--disable-gopher \
--disable-imap \
--disable-pop3 \
--disable-rtsp \
--disable-smtp \
--disable-telnet \
--disable-tftp \
--prefix="$DIR/install_data/php/ext/curl" \
--disable-shared \
$CROSS_COMPILE_FLAGS >> "$DIR/install.log" 2>&1
echo -n " compiling..."
make -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
echo -n " cleaning..."
cd ..
rm -r -f ./curl
echo " done!"

#pthreads
echo -n "[PHP pthreads] downloading $PTHREADS_VERSION..."
wget https://github.com/krakjoe/pthreads/archive/$PTHREADS_VERSION.tar.gz --no-check-certificate -q -O - | tar -zx >> "$DIR/install.log" 2>&1
mv pthreads-$PTHREADS_VERSION "$DIR/install_data/php/ext/pthreads"
echo " done!"


echo -n "[PHP]"
#Amount of RAM isn't known ahead of time, so disable optimizations
OPTIMIZATION="--disable-inline-optimization "
set -e
echo -n " checking..."
cd php
rm -rf ./aclocal.m4 >> "$DIR/install.log" 2>&1
rm -rf ./autom4te.cache/ >> "$DIR/install.log" 2>&1
rm -f ./configure >> "$DIR/install.log" 2>&1
./buildconf --force >> "$DIR/install.log" 2>&1
##LDFLAGS="-R$DIR/install_data/php/ext/libedit/lib -L$DIR/install_data/php/ext/libedit/lib -R'$DIR/install_data/php/ext/ncurses/lib' -L$DIR/install_data/php/ext/ncurses/lib" EXTRA_LDFLAGS="-R$DIR/install_data/php/ext/libedit/lib -L$DIR/install_data/php/ext/libedit/lib -R'$DIR/install_data/php/ext/ncurses/lib' -L$DIR/install_data/php/ext/ncurses/lib" LIBS="-lncurses" CFLAGS=$CFLAGS"-I$DIR/install_data/php/ext/libedit/include" \
sed -i 's/pthreads_working=no/pthreads_working=yes/' ./configure
LIBS="-lpthread -ldl" ./configure $OPTIMIZATION--prefix="$DIR/php5" \
--exec-prefix="$DIR/php5" \
--with-curl="$DIR/install_data/php/ext/curl" \
--with-zlib="$DIR/install_data/php/ext/zlib" \
"$HAVE_LIBEDIT" \
--disable-libxml \
--disable-xml \
--disable-dom \
--disable-simplexml \
--disable-xmlreader \
--disable-xmlwriter \
--disable-cgi \
--disable-session \
--disable-zip \
--disable-debug \
--disable-phar \
--enable-ctype \
--enable-sockets \
--enable-shared=no \
--enable-static=yes \
--enable-shmop \
--enable-pcntl \
--enable-pthreads \
--enable-maintainer-zts \
--enable-zend-signals \
--enable-embedded-mysqli \
--enable-bcmath \
--enable-cli \
--without-pear \
--without-iconv \
--without-pdo \
--without-pdo-sqlite \
--with-zend-vm=$ZEND_VM \
$CROSS_COMPILE_FLAGS  >> "$DIR/install.log" 2>&1
echo -n " compiling..."
if [ "$1" == "android" ]; then
    sed -i 's/-export-dynamic/-all-static/g' Makefile
fi
##ln -s $DIR/install_data/php/ext/libedit/include/readline $DIR/install_data/php/ext/libedit/include/editline
##CFLAGS=$CFLAGS"-I$DIR/install_data/php/ext/libedit/include" LDFLAGS="-R$DIR/install_data/php/ext/libedit/lib -L$DIR/install_data/php/ext/libedit/lib -R'$DIR/install_data/php/ext/ncurses/lib' -L$DIR/install_data/php/ext/ncurses/lib" \
make  -j $THREADS >> "$DIR/install.log" 2>&1
echo -n " installing..."
make install >> "$DIR/install.log" 2>&1
echo " done!"
cd "$DIR"
echo -n "[INFO] Cleaning up..."
rm -r -f install_data/ >> "$DIR/install.log" 2>&1
mv php5/bin/php bin/php
rm -r -f php/ >> "$DIR/install.log" 2>&1
date >> "$DIR/install.log" 2>&1
echo " done!"
echo "[PocketMine] You should start the server now using \"./start.sh\""
echo "[PocketMine] If it doesn't works, please send the \"install.log\" file to the Bug Tracker"
