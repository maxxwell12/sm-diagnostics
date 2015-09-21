#!/usr/bin/env bash

base=${PWD}
release=/tmp/sm-diagnostics-release

rm -rf ${release}
mkdir ${release}

git archive --format zip --worktree-attributes HEAD > ${release}/release.zip

cd ${release}
unzip release.zip -d ./
rm release.zip

# Delete files
rm -rf ${release}/build.sh
rm -rf ${release}/.gitignore
rm -rf ${release}/.gitattributes

# Finally, create the release archive
cd ${release}
find . -type d -exec chmod 0750 {} +
find . -type f -exec chmod 0644 {} +
chmod 0775 .
zip -r release.zip ./
