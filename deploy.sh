#! /bin/bash
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# main config
PLUGINSLUG="query-filter"  #must match with wordpress.org plugin slug
MAINFILE="j-query-filter.php" # this should be the name of your main php file in the wordpress plugin
#SVNUSER="rtcamp" # your svn username


##### YOU CAN STOP EDITING HERE #####
CURRENTDIR=`pwd`

# git config
GITPATH="$CURRENTDIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="https://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on wordpress.org, with no trailing slash

# Detect svn username based on url
SVNUSER=$(cat ~/.subversion/auth/svn.simple/* | grep -A4 $(echo $SVNURL | awk -F// '{print $2}' | cut     -d'/' -f1) | tail -n1)
if [ -z "$SVNUSER" ]
then
	SVNUSER="rtcamp"
fi


# Let's begin...
echo ".........................................."
echo
echo "Preparing to deploy wordpress plugin"
echo
echo ".........................................."
echo

# Check version in readme.txt is the same as plugin file
NEWVERSION1=`grep "^Stable tag" $GITPATH/readme.txt | awk -F' ' '{print $3}' | tr -d '\r'`
echo "readme version: $NEWVERSION1"
#NEWVERSION2=`grep "^Version" $GITPATH/$MAINFILE | awk -F' ' '{print $2}'`
NEWVERSION2=`grep -i "Version" $GITPATH/$MAINFILE | head -n1 | awk -F':' '{print $2}' | awk -F' ' '{print $1}' | tr -d '\r'`
echo "$MAINFILE version: $NEWVERSION2"

if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then echo "Versions don't match. Exiting...."; exit 1; fi

echo "Versions match in readme.txt and PHP file. Let's proceed..."

cd $GITPATH
bash readme.sh $SVNURL
git add README.md
echo -e "Enter a commit message for this new version: \c"
read COMMITMSG
git commit -am "$COMMITMSG"

echo "Tagging new version in git"
set -f
RELEASENOTES="$(cat $GITPATH/readme.txt | awk -v ver="$NEWVERSION1" -v RS='\n\n' '/== Changelog ==/ { foo = 0 } $0 ~ ver { print }' | grep -E '^\*')"
echo $RELEASENOTES
git tag -a "$NEWVERSION1" -m "Version $NEWVERSION1
$RELEASENOTES"
set +f

echo "Pushing latest commit to origin, with tags"
git push origin master
git push origin master --tags

echo
echo "Creating local copy of SVN repo ..."
svn co $SVNURL $SVNPATH

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/

echo "Ignoring github specific files and deployment script"
svn propset svn:ignore "deploy.sh
deploy-common.sh
readme.sh
README.md
.git
.gitattributes
.gitignore
assets" "$SVNPATH/trunk/"

echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/trunk/
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
svn commit --username=$SVNUSER -m "$COMMITMSG"

echo "Creating new SVN tag & committing it"
cd $SVNPATH
svn copy trunk/ tags/$NEWVERSION1/
cd $SVNPATH/tags/$NEWVERSION1
svn commit --username=$SVNUSER -m "Tagging version $NEWVERSION1"

echo "Removing temporary directory $SVNPATH"
rm -fr $SVNPATH/

echo "*** FIN ***"
