#!/usr/bin/env bash

version=0.1.0
filename=sm_diagnostics_v${version}.zip
release=/tmp/release

rm -rf ${release}
mkdir ${release}

git archive --format zip --worktree-attributes HEAD > ${release}/${filename}

cd ${release}
unzip ${filename} -d ./
rm ${filename}.zip

# Delete files
rm -rf ${release}/build.sh
rm -rf ${release}/.gitignore
rm -rf ${release}/.gitattributes

# Finally, create the release archive
cd ${release}
find . -type d -exec chmod 0750 {} +
find . -type f -exec chmod 0644 {} +
chmod 0775 .
zip -r ${filename} ./
