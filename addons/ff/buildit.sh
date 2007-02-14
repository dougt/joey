

cd components
rm *.xpt
/Users/dougt/builds/ff2/mozilla/dist/bin/xpidl -m typelib -I"/Users/dougt/builds/ff2/mozilla/dist/idl" mocoJoey.idl
cd ..
rm *.xpi
cd chrome
zip -r joey.jar *
cd ..
zip -r joey.xpi *

