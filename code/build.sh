#!/bin/sh

githash=`git log --max-count=1 --format=%h`
today=`date +%y%m%d`
ver=$today-$githash
releaseName=rel.$ver


git branch $releaseName \
    && git checkout $releaseName \
    || { echo "Failed to create branch $releaseName"; exit 1; }


make clean; make vlbum


git commit -a -m "$releaseName"

git branch release
git merge -s ours release

git co release && git merge $releaseName

