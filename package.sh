#!/bin/bash

# package.sh - Build the Snippets installer package
# Created by René Kreijveld, 29-03-2026

baseDir=$(pwd)
srcDir=${baseDir}/Src
distroDir=${baseDir}/Distro
packageDir=${baseDir}/Packages
name=snippets
extensionName=${name}
componentFile=${packageDir}/com_${extensionName}.zip
libraryFile=${packageDir}/lib_${extensionName}.zip
finderFile=${packageDir}/plg_finder_${extensionName}.zip
exclude=".git .gitignore \*update*.xml"

version=$(grep '<version>' ${baseDir}/package.xml | sed -r 's#.*<version>([^<]+)</version>.*#\1#')
versionDir="${packageDir}/${version}"
mkdir -p "${versionDir}"

# Build Component installer
cd ${srcDir}
zip -q -r ${componentFile} \
  administrator/components/com_${extensionName} \
  components/com_${extensionName} \
  media/com_${extensionName} \
  --exclude ${exclude}
echo "Created com_${extensionName}.zip"
cd administrator/components/com_${extensionName}
zip -q ${componentFile} ${extensionName}.xml
echo "Added ${extensionName}.xml to com_${extensionName}.zip"

# Build Library installer
cd ${srcDir}/libraries
zip -q -r ${libraryFile} lib_${extensionName}.xml Snippets --exclude ${exclude}
echo "Created lib_${extensionName}.zip"

# Build Finder plugin
cd ${srcDir}/plugins/finder/snippets
zip -q -r ${finderFile} \
  * \
  --exclude ${exclude}
echo "Created plg_finder_${extensionName}.zip"

# Create installer package
cd ${packageDir}
# Delete old version
[ -f ${versionDir}/${name}-${version}.zip ] && rm -f ${versionDir}/${name}-${version}.zip
cp ${baseDir}/package.xml ${packageDir}
echo "Moving zips and package.xml into installer package zip"
zip -m ${versionDir}/${name}-${version}.zip package.xml *.zip
echo "Package ready:"
echo "${versionDir}/${name}-${version}.zip"

cp "${versionDir}/${name}-${version}.zip" "${distroDir}/${name}-${version}.zip"