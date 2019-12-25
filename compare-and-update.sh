#!/bin/bash
git clone https://github.com/mns-llc/public-suffix-data psd

files=( "icann.json" "private.json" )

for file in "${files[@]}"
do
  if cmp running/$file psd/$file; then
    echo "$file differs, the psd needs to be updated"
    cp -fv running/* psd/
    echo "local psd directory can be used to push changes"
    exit 1
  else
    echo "$file is the same in both places"
  fi
done