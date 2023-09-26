#!/bin/sh
#
# FreeScout upgrade script.
# 
# If you need you can create /tools/pre_update.sh script which will be launched in the beginning.
# 
# Available flags:
# --yes - answer yes to everything

DATE_TIME=`date +%Y-%m-%d_%H:%M:%S`
echo -e "Starting updating process: \e[32m${DATE_TIME}\e[0m";

# Check PHP version
php_v=`php -v | grep 'PHP [78]' | wc -l`
if [ $php_v != 1 ]; then
	echo -e "\e[32mChecking PHP version...\e[0m";
	php -v
	echo -e "\e[31mInvalid PHP version (PHP 7/8 is required)\e[0m";
	exit;
fi

echo -e "\e[33mMake sure to create a backup of the application before you continue.\e[0m";

# Determine project root
TOOLS_DIR=`which $0 | xargs dirname`;
if [ $TOOLS_DIR = '' ]; then
	echo -e "\e[31mCould not determine project root folder.\e[0m";
	exit;
fi
PROJECT_ROOT="${TOOLS_DIR}/..";

cd $PROJECT_ROOT;
PROJECT_ROOT=`pwd`;
echo -e "Application root: \e[32m${PROJECT_ROOT}\e[0m"

if [ -f "${TOOLS_DIR}/pre_update.sh" ]; then
	echo "Including pre_update.sh"
	source "${TOOLS_DIR}/pre_update.sh";
fi

#if [ -f "${PROJECT_ROOT}/.gitcommit" ]; then
#	gitcommit=`more "${PROJECT_ROOT}/.gitcommit"`
#	echo -e "Last commit: \e[32m${gitcommit}\e[0m";
#fi

# Get flags
yes=false;
while test $# -gt 0; do
  case "$1" in
    --yes)
      yes=true
      shift
      break
      ;;
    *)
	  shift
      ;;
  esac
done

# Check if git is installed
git_installed=`command -v git`;
if [ -z "$git_installed" ]; then
	echo -e "\e[31mGit is not installed. Please intall Git and restart updating.\e[0m";
	exit;
fi

# Check if there is a Git repo in the project folder
git_inited=`git status | grep 'On branch' | wc -l`

if [ $git_inited != 1 ]; then
	# Initizalize Git and install latest version of the app
	
	printf "\nGit repository is not initizalized yet. Would you like to install the latest version of the application via Git (this will replace existing files)? (Y/n) [n]:"
	if [ $yes = true ]; then
		confirm_gitinit='Y';
		printf "\n"
	else
		read confirm_gitinit;
	fi
	if [ $confirm_gitinit != "Y" ]; then
	    exit;
	fi
	
	git init
	git remote add origin https://github.com/freescout-helpdesk/freescout.git
	git fetch

	# If user stops here next time he will get: "Your Git repository is on a wrong branch"
	#printf "\nReady to install application from remote repository. Continue? (Y/n) [n]:"
	#read confirm_checkout;
	#if [ $confirm_checkout != "Y" ]; then
	#    exit;
	#fi
	git checkout -t -f origin/dist
	git checkout dist
else
	# Update app via Git
	
	# Check branch
	branch=`git branch | grep '* ' | sed 's#* ##g'`;
	if [[ $branch != 'dist' && $branch != 'master' ]]; then
		echo -e "\e[31mYour Git repository is on a wrong branch: ${branch}. Upgrading is possible only for dist or master branches. Please switch to the correct branch and restart upgdate.\e[0m";
		exit;
	fi

	echo -e "Current branch: \e[32m${branch}\e[0m";

	git fetch

	new_commits=`git log "$branch..origin/$branch" --pretty=format:"%h %ad | %s%d [%an]" --graph --date=short | wc -l`;
	if [ $new_commits = 0 ]; then
		echo -e "\e[32mYou already have the latest version of the application, no upgrade needed.\e[0m";
		exit;
	fi

	# Check if there are uncommited files
	has_unstaged=`git status | grep -E '(Changes not staged for commit|Untracked files)' | wc -l`
	if [ $has_unstaged = 1 ]; then
		git status;
		printf "\nThere are uncommitted files, they may be overwritten during upgrade. Continue? (Y/n) [n]:"
		if [ $yes = true ]; then
			confirm_overwrite='Y';
			printf "\n"
		else
			read confirm_overwrite;
		fi
		if [ $confirm_overwrite != "Y" ]; then
		    exit;
		fi
		git checkout .
	fi

	printf "\nNew commits:\n";
	if [ $yes = false ]; then
		git log "$branch..origin/$branch" --pretty=format:"%h %ad | %s%d [%an]" --graph --date=short
	fi

	printf "\nPull updates and continue? (Y/n) [n]:"
	if [ $yes = true ]; then
		confirm_pull='Y';
		printf "\n"
	else
		read confirm_pull;
	fi
	if [ $confirm_pull != "Y" ]; then
	    exit;
	fi

	git pull -f

	printf "\nPulling updates finished. Continue? (Y/n) [n]:"
	if [ $yes = true ]; then
		confirm_continue='Y';
		printf "\n"
	else
		read confirm_continue;
	fi
	if [ $confirm_continue != "Y" ]; then
	    exit;
	fi

	printf "\nStatus:\n"
	git status

	# If branch is master, run composer install
	if [ $branch = 'master' ]; then
		printf "\nComposer dependencies will be installed. Continue? (Y/n) [n]:"
		if [ $yes = true ]; then
			confirm_install='Y';
			printf "\n"
		else
			read confirm_install;
		fi
		if [ $confirm_install != "Y" ]; then
		    exit;
		fi

		# Check if composer intalled
		composer_installed=`composer -V | grep version | wc -l`
		if [ $composer_installed != 1 ]; then
			echo -e "\e[31mComposer command not found. Please intall composer (https://getcomposer.org/download/) and restart upgrade.\e[0m";
			exit;
		fi

		composer install;

		printf "\nStatus:\n"
		git status
	fi

fi


printf "\nClearing cache:\n"
php artisan freescout:clear-cache
#php artisan package:discover

printf "Run DB migration and continue? (Y/n) [n]:"
if [ $yes = true ]; then
	confirm_migrate='Y';
	printf "\n"
else
	read confirm_migrate;
fi
if [ $confirm_migrate != "Y" ]; then
    exit;
fi
if [ $yes = true ]; then
	php artisan migrate --force
else
	php artisan migrate
fi

php artisan queue:restart

printf "\nWould you like to update modules? (Y/n) [n]:"
if [ $yes = true ]; then
	confirm_modules='Y';
	printf "\n"
else
	read confirm_modules;
fi
if [ $confirm_modules != "Y" ]; then
    exit;
fi

php artisan freescout:module-update

if [ -f "${TOOLS_DIR}/post_update.sh" ]; then
	echo "Including post_update.sh"
	source "${TOOLS_DIR}/post_update.sh";
fi
