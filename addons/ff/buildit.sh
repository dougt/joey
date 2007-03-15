

pushd components
rm *.xpt
/Users/dougt/builds/ff2/mozilla/dist/bin/xpidl -m typelib -I"/Users/dougt/builds/ff2/mozilla/dist/idl" mocoJoey.idl
popd

rm -rf work
mkdir work

cp -R chrome work/
cp -R defaults work/
cp -R components work/
cp chrome.manifest work/
cp install.js work/
cp install.rdf work/

pushd work


find . -name .svn | xargs rm -rf
find . -name .DS_Store | xargs rm -rf

pushd chrome
rm joey.jar
zip -r joey.jar *
popd

zip -r joey.xpi *


popd
