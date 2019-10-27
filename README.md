# Haremasu

Quick and dirty PHP svn-to-git script: it exports subversion history into git

# What it does ?

The `haremasu.php` script is simply a shortcut between `svn` and `git` commands.
It exports the subversion history from `svn log` into a temporary file, and then
imports it into the locally cloned git repo.

Very quick and dirty ;-)

# How to use it ?

1. The script uses the `svn` and `git` binaries, so make sure both are available.
You can check by doing a simple `which svn` and `which git` checks.

2. Start with cloning a git repo. This is where the subversion/svn revisions
will be imported.
```
git clone git@github.com:kktsvetkov/wp-hefo.git
```

3. Find the subversion/svn repo URL. I advice that you get a local copy of the
repo instead of using a remote one, because it will work faster.
```
svn checkout https://plugins.svn.wordpress.org/wp-hefo/
```

4. The arguments for the script are the svn url and the folder of the locally
cloned git repo. If the subversion/svn project has a local copy, use its folder
as the svn url.
```
php haremasu.php svn-url local-git-repo-folder
```

5. Here is what the output should look like:
```
php haremasu.php ~/svn.wp-hefo/ ~/github.wp-hefo/

HAREMASU: export svn to git
 < Source Folder: /Users/kt/svn.wp-hefo/
 < Source SVN URL: < https://plugins.svn.wordpress.org/wp-hefo

 > Target Folder: /Users/kt/github.wp-hefo/
 > Target Git URL: > git@github.com:kktsvetkov/wp-hefo.git

 < Extracting SVN History...
 < Done.
 < SVN History: 5 revisions

 < [1/5] r40918 | Mrasnika | Wed, 16 Apr 2008 10:45:06 +0000
 < [2/5] r40926 | Mrasnika | Wed, 16 Apr 2008 10:55:58 +0000
 < [3/5] r40927 | Mrasnika | Wed, 16 Apr 2008 10:56:35 +0000
 < [4/5] r105060 | Mrasnika | Wed, 25 Mar 2009 11:53:07 +0000
 < [5/5] r105071 | Mrasnika | Wed, 25 Mar 2009 12:50:53 +0000
```
You can see the result at https://github.com/kktsvetkov/wp-hefo/commits/master

# Partial History Exports

You can export only a file or a folder from subversion/svn. You just need to
use the svn source URL to point to that file or folder. Here are few examples:
```
php haremasu.php ~/svn.wp-hefo/trunk/hefo.php ~/github.wp-hefo/
php haremasu.php http://plugins.svn.wordpress.org/wp-hefo/tags/0.2/hefo.php ~/github.wp-hefo/
```
