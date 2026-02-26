#!/bin/bash
# fix_bundling.sh
cd calldeskapp
rm -rf node_modules
rm package-lock.json
npm install
npx expo start --clear
