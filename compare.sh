#!/bin/bash
git clone https://github.com/mns-llc/public-suffix-data psd

files = ( "icann.json" "private.json" )

for file in "${files[@]}"
do
  if cmp -s "running/$file" "psd/$file"; then
    echo "$file differs, the PSD needs to be updated"
    cp -fv running/* psd/
    exit 1
  else
    echo "$file is the same in both places"
  fi
done