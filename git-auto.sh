#!bin/sh

curdate=`date +"%F-%T"`

curstatus=`git status --short`


git status 

git add -A

git commit -a -m "$curstatus"

git pull

git push