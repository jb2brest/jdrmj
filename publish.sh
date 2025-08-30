#!/bin/bash
# ./publish.sh "1.4.13" "Oubli mot de passe 1 "
sed -i "s|<version_tag>|<version_tag>\n- $1 : $2|g" README.md
#git add *.php *.js *.md *.png *.jpg *.sh *.css
git add *.php
git add *.sh
git add *.md
git add *.sql
git add *.txt
git add *.htaccess
git add *.py
git commit -m "$2"
git tag -a $1 -m "$2"
git push
git push origin --tags