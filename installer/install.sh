#!/usr/bin/env bash

# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

# This install script was written with apt based systems in mind, but should work on other systems as well
# This script has only been tested on Red Hat Enterprise Linux 7.5.
# This script will soon be tested on Ubuntu 14.04 WSL, Ubuntu 16.04, Fedora Server 27, CentOS 7.4, and Arch Linux as of ~16/05/2018
# Not all code paths have been tested, due to restrictions on the testbed servers. Whilst I have resonable confidence in the untested code, it may error or damage things. Do not use in production (yet).

# Do not set -e

# For debugging:
# set -x

# For latest release use: https://api.github.com/repos/BytewaveMLP/sleeti/tarball
DOWNLOAD_URL="https://github.com/BytewaveMLP/sleeti/archive/master.tar.gz"

VERSION=0.0.1

INSTALL_NGINX=1
INSTALL_MARIADB=1
INSTALL_PHP=1
INSTALL_KOMPOSER=1

CONFIGURE_NGINX=1
DETECT_NGINX=1

DATABASE_ROOT_PASSWORD=''
DATABASE_USER_PASSWORD=''

SKIP_OS_SUPPORT_CHEQUE=0

PHP_INSTALLED_BINARY='php'

BLACK='\033[0;30m'
RED='\033[0;31m'
GREEN='\033[0;32m'
ORANGE='\033[0;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
LIGHT_GREY='\033[0;37m'
DARK_GREY='\033[1;30m'
LIGHT_RED='\033[1;31m'
LIGHT_GREEN='\033[1;32m'
YELLOW='\033[1;33m'
LIGHT_BLUE='\033[1;34m'
LIGHT_PURPLE='\033[1;35m'
LIGHT_CYAN='\033[1;36m'
WHITE='\033[1;37m'
NC='\033[0m'

command_exists() {
  command -v "$@" > /dev/null 2>&1
}

get_system_info() {
  ./sherlock
}

get_base_operating_system() {
  local system
  system=$(source <(get_system_info) && echo "$DISTRIBUTION")

  if [ "$system" == "" ]; then
    # Detect operating system based on the present package manager
    if command_exists "apt"; then
      system="debian"
    fi

    if command_exists "yum"; then
      system="redhat"
    fi

    if command_exists "pacman"; then
      system="arch"
    fi
  fi

  echo "$system"
}

get_family() {
  local family
  family=$(source <(get_system_info) && echo "$FAMILY")

  if [ "$family" == "" ]; then
    # Detect operating system based on the present package manager. 
    if command_exists "apt"; then
      family="debian"
    fi

    if command_exists "yum" || command_exists "dnf"; then
      family="rh"
    fi

    if command_exists "pacman"; then
      family="arch"
    fi
  fi

  echo "$family"
}

get_distribution() {
	(source <(get_system_info) && echo "$DERIVATIVE")
}

get_release() {
  (source <(get_system_info) && echo "$RELEASE")
}

get_march() {
  (source <(get_system_info) && echo "$MACH")
}

get_codename() {
  (source <(get_system_info) && echo "$CODENAME")
}

is_operatingsystem() {
  lsb_dist=$(get_distribution)
	lsb_dist="$(echo "$lsb_dist" | tr '[:upper:]' '[:lower:]')"

  if [ "$lsb_dist" == "$1" ]; then
    return 0
  fi
  
  return 1
}

is_family() {
  lsb_family=$(get_family)
  lsb_family="$(echo "$lsb_family" | tr '[:upper:]' '[:lower:]')"

  if [ "$lsb_family" == "$1" ]; then
    return 0
  fi
  
  return 1
}

is_debian() {
  if is_operatingsystem "debian"; then
    return 0
  fi
  
  return 1
}

is_ubuntu() {
  if is_operatingsystem "ubuntu"; then
    return 0
  fi
  
  return 1
}

is_rhel() {
  if is_operatingsystem "rhel"; then
    return 0
  fi
  
  return 1
}

is_centos() {
  if is_operatingsystem "centos"; then
    return 0
  fi
  
  return 1
}

is_dist_supported() {
  if is_debian; then
    case "$(get_codename)" in
      jessie)
        return 0;;
      stretch)
        return 0;;
      wheezy)
        print_error "Support for Debian Wheezy is ending on May 31st, 2018. It is recommended that you upgrade before then."
        return 0;;
      buster)
        return 0;;
      sid)
        return 0;;
      *)
        print_error "It does not appear that you current version of Debian $(get_codename) is supported. Please upgrade to at least Debian Jessie."
        return 1;;
    esac
  fi

  if is_ubuntu; then
    case "$(get_codename)" in
      xenial)
        return 0;;
      trusty)
        return 0;;
      artful)
        print_error "Support for Ubuntu 17.10 is ending in July, 2018. It is recommended that you upgrade before then."
        return 0;;
      bionic)
        return 0;;
      *)
        print_error "It does not appear that you current version of Ubuntu $(get_release) is supported. Please upgrade to at least Ubuntu 16.04."
        return 1;;
    esac
  fi

  if is_rhel || is_centos; then
    case "$(get_release)" in
      7.*)
        return 0;;
      6.*)
        if is_centos; then
          print_error "Full updates for CentOS 6 have ended. It is recommended that you upgrade to CentOS 7 to ensure that the newest packages are available."
        fi

        return 0;;
      *)
        print_error "It does not appear that your current version of $(get_distribution) $(get_release) is supported. Please upgrade to at least $(get_distribution) 7."
        return 1;;
    esac
  fi
}

print_error() {
  printf "$RED%s\\n$NC" "$*" >&2
}

print_debug() {
  printf "$LIGHT_BLUE%s\\n$NC" "$*"
}

print_info() {
  printf "$YELLOW%s\\n$NC" "$*"
}

print_extra() {
  printf "$GREEN%s\\n$NC" "$*"
}

ask_yn() {
  echo -en "$LIGHT_GREEN$1 "
  read -rp "(y/n) " answer
  echo -en "$NC"

  case ${answer:0:1} in
      y|Y )
        return 0
      ;;
      * )
        return 1
      ;;
  esac
}

ask() {
  echo -en "$LIGHT_GREEN$1"
  read -rp " " answer
  echo -en "$NC"
  
  eval "$2='$answer'"
}

detect() {
  if [ "$(command_exists "$1")" == 1 ]; then
    return 1
  fi

  if [ -x "/usr/sbin/$1" ]; then
    return 1
  fi

  if [ -x "/usr/bin/$1" ]; then
    return 1
  fi

  if [ -x "/usr/local/sbin/$1" ]; then
    return 1
  fi

  if [ -x "/usr/local/bin/$1" ]; then
    return 1
  fi

  if is_family "debian"; then
    if dpkg -l "$1" 2> /dev/null | grep -E "îi.*$1" > /dev/null 2>&1; then
      return 1
    fi
  fi
}

detect_nginx() {
  detect nginx
  return "$?"
}

detect_apache() {
  detect apache2
  return "$?"
}

detect_lighttpd() {
  detect lighttpd
  return "$?"
}

detect_webserver() {
  detect_nginx
  if [ "$?" == 1 ]; then
    print_extra "NGINX detected. Not installing."
    DETECT_NGINX=0
    return 1
  else
    print_debug "NGINX not detected. Proceeding."
  fi

  detect_apache
  if [ "$?" == 1 ]; then
    print_extra "Apache detected. Not installing."
    return 1
  else
    print_debug "Apache not detected. Proceeding."
  fi

  detect_lighttpd
  if [ "$?" == 1 ]; then
    print_extra "Lighttpd detected. Not installing."
    return 1
  else
    print_debug "Lighttpd not detected. Proceeding."
  fi

  return 0
}

detect_mysql() {
  detect mysql
  return "$?"
}

detect_mariadb() {
  detect mysql
  if [ "$?" == 1 ]; then
    return 1
  fi

  if is_family "debian"; then
    if dpkg -l "mariadb-server" 2> /dev/null | grep -E "îi.*mariadb" > /dev/null 2>&1; then
      return 1
    fi
  fi

  return 0
}

detect_database() {
  detect_mysql
  if [ "$?" == 1 ]; then
    print_extra "MySQL detected. Not installing."
    return 1
  else
    print_debug "MySQL not detected. Proceeding."
  fi

  detect_mariadb
  if [ "$?" == 1 ]; then
    print_extra "MariaDB detected. Not installing."
    return 1
  else
    print_debug "MariaDB not detected. Proceeding."
  fi

  return 0
}

detect_php() {
  detect php
  if [ "$?" == 1 ]; then
    return 1
  fi

  detect php7
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php7'
    return 1
  fi

  detect php7.0
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php7.0'
    return 1
  fi

  detect php7.1
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php7.1'
    return 1
  fi

  detect php7.2
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php7.2'
    return 1
  fi

  detect php70
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php70'
    return 1
  fi

  detect php71
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php71'
    return 1
  fi

  detect php72
  if [ "$?" == 1 ]; then
    PHP_INSTALLED_BINARY='php72'
    return 1
  fi

  return 0
}

cheque_php_version() {
  $PHP_INSTALLED_BINARY -r 'exit(version_compare(PHP_VERSION_ID, 70000) >= 0 ? 0 : 1 );' > /dev/null 2>&1

  case "$?" in
    0)
      print_extra "PHP $($PHP_INSTALLED_BINARY -r 'echo PHP_VERSION;') is of adequate version."
      return 1
    ;;
    *)
      print_error "Your PHP version is not of an adequate version or is damaged."
      print_info "Setting PHP-CLI 7.2 to install."
      INSTALL_PHP=0
      return 0
    ;;
  esac

  return 0
}

detect_php_interpreter() {
  detect_php
  if [ "$?" == 1 ]; then
    print_extra "PHP detected. Chequeing version."
    cheque_php_version
    return "$?"
  else
    print_debug "PHP not detected. Proceeding."
  fi

  return 0
}

detect_komposer() {
  detect composer
  if [ "$?" == 1 ]; then
    print_extra "Composer detected. Not installing."
    return 1
  else
    if [ -f composer.phar ] || [ -f composer.php ]; then
      print_extra "Composer detected. Not installing."
      return 1
    fi

    print_debug "Composer not detected. Proceeding."
    INSTALL_KOMPOSER=0
  fi

  return 0
}

install_nginx() {
  local release dist
  print_debug "Installing Prerequisites..."
  if is_family "debian"; then
    apt-get install -yqq software-properties-common apt-transport-https curl
    print_extra "Adding NGINX Signing Key to apt-keyring."
    curl -fsSL https://nginx.org/keys/nginx_signing.key | apt-key add --
    
    print_extra "Adding NGINX Apt repository."
    add-apt-repository "deb https://nginx.org/packages/$(get_distribution | tr '[:upper:]' '[:lower:]')/ $(get_codename) nginx"
    
    print_extra "Updating Apt Cache."
    apt-get update -yqq
    
    print_extra "Installing NGINX using Apt."
    apt-get install -yqq nginx
  fi

  if is_family "rh"; then
    yum -y -q install curl coreutils
    print_extra "Adding NGINX Signing Key to rpm-keyring." 
    rpm --import https://nginx.org/keys/nginx_signing.key

    print_extra "Adding NGINX yum repository."

    case "$(get_distribution)" in
      fedora)
        release=$(get_release | cut -b-2)
        dist=centos
      ;;
      *)
        release=$(get_release | cut -b-1)
        dist=$(get_distribution | tr '[:upper:]' '[:lower:]')
      ;;
    esac

    cat <<- EOF > /etc/yum.repos.d/nginx.repo
[nginx]
name=NGINX Repo
baseurl=http://nginx.org/packages/mainline/$dist/$release/$(get_march)/
gpgcheck=1
enabled=1
EOF

    print_extra "Installing NGINX using yum."
    yum -y -q install nginx
  fi

  if is_family "arch"; then
    # The NGINX in the community repo is in line with the mainline nginx repo

    pacman -Syq --no-confirm nginx-mainline > /dev/null 2>&1
  fi

  print_info "NGINX has been installed."
}

install_mariadb() {
  local release
  if is_family "debian"; then
    print_extra "Adding MariaDB Signing Key to apt-keyring."
    apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
    
    print_extra "Adding MariaDB Apt repository."
    add-apt-repository "deb http://mirrors.digitalocean.com/mariadb/repo/10.2/$(get_distribution | tr '[:upper:]' '[:lower:]')/ $(get_codename) main"
    
    print_extra "Updating Apt Cache."
    apt-get update -yqq
    
    print_extra "Installing MariaDB using Apt."
    apt-get install -yqq mariadb-server
  fi

  if is_family "rh"; then
    print_extra "Adding MariaDB Signing Key to rpm-keyring." 
    rpm --import https://yum.mariadb.org/RPM-GPG-KEY-MariaDB

    print_extra "Adding MariaDB yum repository."
    # The Fedora version is two characters, whilst CentOS and RHEL is one.
    case "$(get_distribution)" in
      fedora)
        release=$(get_release | cut -b2)
      ;;
      *)
        release=$(get_release | cut -b1)
      ;;
    esac

    cat <<- EOF > /etc/yum.repos.d/mariadb.repo
[mariadb]
name=MariaDB
baseurl=http://yum.mariadb.org/10.2/$(get_distribution | tr '[:upper:]' '[:lower:]')$release-amd64
gpgcheck=1
enabled=1
EOF

    print_extra "Installing MariaDB using yum."
		case "$(get_distribution)" in
      fedora)
        dnf -y -q install mariadb-server
      ;;
      *)
        yum -y -q install MariaDB-server
      ;;
    esac
    systemctl start mysqld.service
    systemctl start mariadb.service
  fi

  if is_family "arch"; then
    # MariaDB in the community repo is in line with the upstream MariaDB repo

    pacman -Syq --no-confirm mariadb > /dev/null 2>&1
  fi

  print_info "MariaDB has been installed."
}

install_php() {
  if is_family "debian"; then
    # Uso los Debs de Sury por php7
    print_extra "Adding deb.sury.org Signing Key to apt-keyring."
    apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x4F4EA0AAE5267A6C
    
    print_extra "Adding PHP Apt repository."
    if is_ubuntu; then
      add-apt-repository "deb http://ppa.launchpad.net/ondrej/php/ubuntu $(get_codename) main"
    fi

    if is_debian; then
      add-apt-repository "deb https://packages.sury.org/php $(get_codename) main"
    fi

    print_extra "Updating Apt Cache."
    apt-get update -yqq
    
    print_extra "Installing PHP using Apt."
    apt-get install -yqq php7.2-fpm php7.2 php7.2-dom php7.2-json php7.2-pdo php7.2-mbstring php7.2-zip
    PHP_INSTALLED_BINARY='php7.2'
  fi

  if is_family "rh"; then
    local packages
    packages=(php-cli php-json php-pdo php-mysqlnd php-dom php-mbstring php-pecl-zip php-pecl-mcrypt php-xml)

    print_extra "Installing Prerequisites..."
    
    print_extra "Adding Remi PHP Signing Keys to rpm-keyring." 
    rpm --import https://rpms.remirepo.net/RPM-GPG-KEY-remi
    rpm --import https://rpms.remirepo.net/RPM-GPG-KEY-remi2017
    rpm --import https://rpms.remirepo.net/RPM-GPG-KEY-remi2018

    case "$(get_distribution)" in
      fedora)
        print_extra "Enabling Remi's PHP Repo."
        dnf -y -q install "http://rpms.remirepo.net/fedora/remi-release-$(get_release | cut -b-2).rpm"
      ;;
      *)
        print_extra "Installing EPEL Repo."
        yum -y -q install "https://dl.fedoraproject.org/pub/epel/epel-release-latest-$(get_release | cut -b1).noarch.rpm"
        print_extra "Enabling Remi's PHP RPM Repo."
        yum -y -q install "http://rpms.remirepo.net/enterprise/remi-release-$(get_release | cut -b1).rpm"
        print_extra "Installing yum-utils."
        yum -y -q install yum-utils

        if is_rhel; then
          print_extra "Enabling Optional RPM repo."
          if [ "$(get_release | cut -b1)" -lt 7 ]; then
            rhn-channel --add --channel="rhel-$(uname -i)-server-optional-$(get_release | cut -b1)"
          else
            subscription-manager repos --enable="rhel-$(get_release | cut -b1)-server-optional-rpms"          
          fi
        fi
      ;;
    esac

    if [ "$INSTALL_NGINX" -eq 0 ]; then
      packages+=(php72-php-fpm)
    fi

		# En Fedora
		# sudo dnf --enablerepo=remi --enablerepo=remi-php72 install php-cli php-pdo php-mysqlnd php-mbstring php-mcrypt php-xml
    print_extra "Installing PHP using yum."
    yum -y -q --enablerepo=remi --enablerepo=remi-php72 install "${packages[@]}"
    detect_php
  fi

  if is_family "arch"; then
    local packages
    packages=(php)

    if [ $INSTALL_NGINX -eq 0 ]; then
      packages+=(php-fpm)
    fi

    # PHP in the extra repo is in line with the latest PHP release
    pacman -Syq --no-confirm "${packages[@]}" > /dev/null 2>&1
    PHP_INSTALLED_BINARY='php'
  fi

  print_info "PHP has been installed."
}

install_komposer() {
  print_extra "Fetching latest Composer signature."
  EXPECTED_SIGNATURE=$(curl -fsSL https://composer.github.io/installer.sig)
  print_extra "Fetching latest Composer installer."
  curl -fsSLo composer-setup.php https://getcomposer.org/installer
  ACTUAL_SIGNATURE=$($PHP_INSTALLED_BINARY -r "echo hash_file('SHA384', 'composer-setup.php');")

  print_extra "Comparing expected installer signature with actual installer signature."
  if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
      print_error 'ERROR: Invalid installer signature'
      rm composer-setup.php
      exit 1
  fi

  print_extra "Signatures match."

  $PHP_INSTALLED_BINARY composer-setup.php
  
  if [ "$?" == 0 ]; then
    rm composer-setup.php
  fi

  print_info "Composer has been installed."
}

configure_nginx() {
  local map=(domain tls_enable tls_cert_path tls_key_path dhparam_path hsts_enable) settings=() ans

  ask "What is the domain of this webserver?" ans
  settings+=(ans)

  ask_yn "Would you like to enable TLS?"
  if [ "$?" == 0 ]; then
    settings+=(0)

    ask_yn "Do you already have a TLS certificate available?"
    if [ "$?" == 0 ]; then
      ask "What is the full path to the certificate?" ans
      settings+=(ans)

      ask "What is the full path to the certificate key?" ans
      settings+=(ans)
    else
      ask_yn "Would you like to obtain a free certificate from Let's Encrypt?"
      if [ "$?" == 0 ]; then
        # This LE section is currently untested as a dedicated server for testing was not yet available. It should still theoretically work correctly.
        local flags=() ans
        ask_yn "Do you agree the ACME Subscriber Agreement? (Cannot use Let's Encrypt if you do not)"
        if [ "$?" == 0 ]; then
          flags+=(--agree-tos)
          ask_yn "Would you like to register an account with the EFF? (Used for renewal notifications)"
          if [ "$?" == 0 ]; then
            ask "What email would you like to use for the account?" ans
            flags+=("-m $ans")
          else
            flags+=(--register-unsafely-without-email)
          fi
          print_extra "Installing Certbot from the EFF."
          print_extra "Installing Python and Certbot..."
          if is_family "rh"; then
            yum -y -q install python2 certbot
            certbot -n -q "${flags[@]}" --standalone --http-01-address "${map[0]}" --preferred-challenges http
          fi
          if is_family "debian"; then
            apt-get install -yqq python
            curl -fsSLO https://dl.eff.org/certbot-auto
            chmod a+x certbot-auto
            ./certbot-auto --no-bootstrap -n -q "${flags[@]}" --standalone --http-01-address "${map[0]}" --preferred-challenges http
          fi
        else
          print_extra "Generating Self-Signed Certificate. This will take some time."
          mkdir -p /etc/nginx/ssl > /dev/null 2>&1
          openssl req -newkey rsa:4096 -nodes -keyout "/etc/nginx/ssl/${map[0]}.key.pem" -sha256 -subj "CN=${map[0]}"-x509 -days 730 -out "/etc/nginx/ssl/${map[0]}.cert.pem"
          settings+=("/etc/nginx/ssl/${map[0]}.key.pem" "/etc/nginx/ssl/${map[0]}.cert.pem")         
        fi
      fi
    fi

    print_extra "Generating new dhparam. This will take some time."
    mkdir -p /etc/nginx/ssl > /dev/null 2>&1

    # Use "DSA-like" dhparams, which are far faster to compute. The default "strong-prime" dhparam can take hours to compute and may not be any more computationally secure. See https://security.stackexchange.com/questions/95178/diffie-hellman-parameters-still-calculating-after-24-hours
    openssl dhparam -dsaparam -out /etc/nginx/ssl/dhparam.pem 4096
    settings+=(/etc/nginx/ssl/dhparam.pem)

    ask_yn "Would you like to enable HSTS?"
    settings+=("$?")
  else
    settings+=(1 /bin/false /bin/false /bin/false)
  fi

  rm -f nginx.config

  for i in "${!settings[@]}"
  do
    (echo "$(echo "${map[$i]}" | tr '[:lower:]' '[:upper:]')=${settings[$i]}" >> ./nginx.config) > /dev/null
  done

  print_extra "Generating NGINX config."
  $PHP_INSTALLED_BINARY setup.php nginx-setup
  print_extra "Config Generated."
  rm nginx.config
  mkdir -p /etc/nginx/conf.d/ > /dev/null 2>&1
  mv sleeti.conf /etc/nginx/conf.d/sleeti.conf
  print_extra "NGINX configured."
}

# Portions borrowed from mysql_secure_installation
configure_database() {
  local passwd

  source ./mysql_secure_installation

  if [ $INSTALL_MARIADB -eq 0 ]; then
    # Generate a random root password
    passwd=$(tr -dc A-Za-z0-9_ < /dev/urandom | head -c 24 | xargs)
    (echo -n "$passwd" | tee > /root/mysql_root_passwd.cnf) > /dev/null 2>&1
		chmod 400 /root/mysql_root_passwd.cnf
    set_root_password "$passwd"
    print_debug "Root password is now: $passwd"
  else
    print_extra "The database was previously installed."
    ask "What is the root password? (Blank for none)" passwd
  fi

  print_extra "Removing anonymous access."
  remove_anonymous_users "$passwd"

  print_extra "Disabling remote root access."
  remove_remote_root "$passwd"

  print_extra "Removing test database."
  remove_test_database "$passwd"

  print_extra "Reloading privilege tables."
  reload_privilege_tables "$passwd"

  DATABASE_ROOT_PASSWORD=$passwd

  print_extra "Database Configured."
  return 0
}

configure_sleeti() {
  local username password=a passwordConfirm=b email initalizeDatabase=0 dbName

	#source ./mysql_secure_installation
  
	print_extra "Importing sleeti sql configuration."
	ask "What would you like the database to be named?" dbName

	#x=$(do_query_count "SHOW DATABASES LIKE 'sl_sleeti';" "$DATABASE_ROOT_PASSWORD")
	$PHP_INSTALLED_BINARY setup.php sql get-database-like "$dbName" "$DATABASE_ROOT_PASSWORD"
	if [ "$?" -gt 0 ]; then
		ask_yn "The '$dbName' database already exists. Would you like to reinitalize it? (Will clear database)"

		if [ "$?" == 0 ]; then
			$PHP_INSTALLED_BINARY setup.php sql drop-database "$dbName" "$DATABASE_ROOT_PASSWORD"
		else
			initalizeDatabase=1
		fi
	fi

	if [ "$initalizeDatabase" == 0 ]; then
		$PHP_INSTALLED_BINARY setup.php sql create-database-and-default-tables "$dbName" "$DATABASE_ROOT_PASSWORD"
		$PHP_INSTALLED_BINARY setup.php sql create-db-user "$dbName" "$DATABASE_USER_PASSWORD" "$DATABASE_ROOT_PASSWORD"
	fi
  
	#x="$(do_query_count "SELECT COUNT(*) FROM \`sl_sleeti\`.\`users\` WHERE 'id' = 0;" "$DATABASE_ROOT_PASSWORD")"
	$PHP_INSTALLED_BINARY setup.php sql get-admin-user "$dbName" "$DATABASE_ROOT_PASSWORD"

	if [ "$?" -gt 0 ]; then
		print_extra "There is already an administrative user in the database. Skipping user initalization."
	else
		ask "What would you like the admin username to be?" username
		ask "What would you like the admin email to be?" email

		until [ "$password" == "$passwordConfirm" ]
		do
			ask "What would you like the admin password to be?" password
			ask "Confirm admin password:" passwordConfirm

			if [ "$password" != "$passwordConfirm" ]; then
				print_error "Passwords do not match"
			fi
		done

		rm -f ./sleeti.config

		cat << EOF >> ./sleeti.config
USERNAME=$username
EMAIL=$email
PASSWORD=$password
EOF

		$PHP_INSTALLED_BINARY setup.php sleeti-config
	fi

	print_extra "Sleeti Ready."
	rm -f sleeti.config

  return 0
}

install_sleeti() {
  local ans settings=(site-title site-upload-path password-cost) passwd configureSleeti=0

  print_extra "Installing Prerequisites... (This may take some time)"
  if is_family "rh"; then
    yum -y -q install php72-php-cli php72-php-json php72-php-pdo php72-php-dom php72-php-mbstring php72-php-mysql php72-php-pecl-zip git openssl
  fi

  if is_family "debian"; then
    apt-get install -yqq php7.2-dom php7.2-json php7.2-pdo php7.2-mbstring php7.2-mysql php7.2-zip git openssl
  fi

  print_extra "Fetching Sleeti."
	if [ -f sleeti.tar.gz ]; then
		ask_yn "Sleeti has already been downloaded. Would you like to download it again?"
		if [ "$?" == 0 ]; then
			curl -fsSLo sleeti.tar.gz "$DOWNLOAD_URL"
		fi
	else
		curl -fsSLo sleeti.tar.gz "$DOWNLOAD_URL"
	fi

  print_extra "Extracting Sleeti to current directory."
	if [ -d app/ ] && [ -d bootstrap/ ] && [ -d public/ ] && [ -d resources/ ]; then
		ask_yn "Sleeti has already been extracted. Would you like to extract it again?"
		if [ "$?" == 0 ]; then
			tar --strip-components=1 -xzf sleeti.tar.gz sleeti-master
		fi
	else
		tar --strip-components=1 -xzf sleeti.tar.gz sleeti-master
	fi

  print_extra "Installing dependancies with Composer"
	if [ -d vendor/ ]; then
		ask_yn "Composer dependancies have already been installed. Would you like to install them again?"
		if [ "$?" == 0 ]; then
			$PHP_INSTALLED_BINARY composer.phar install --no-suggest -o
		fi
	else
			$PHP_INSTALLED_BINARY composer.phar install --no-suggest -o
	fi

	if [ $INSTALL_MARIADB -eq 0 ]; then
		passwd=$(tr -dc A-Za-z0-9_ < /dev/urandom | head -c 16 | xargs)
		(echo -n "$passwd" | tee > /root/mysql_user_passwd.cnf) > /dev/null 2>&1
		chmod 400 /root/mysql_user_passwd.cnf

		DATABASE_USER_PASSWORD=$passwd
	fi

  print_extra "Starting Sleeti Config Setup."
  rm -f install.config

	if [ -f config/config.json ]; then
		ask_yn "It appears Sleeti has already been configured. Would you like to reconfigure it?"

		configureSleeti="$?"
	fi

	if [ "$configureSleeti" == 0 ]; then
		for i in "${settings[@]}"
		do
			if [[ "$i" = 'site-upload-path' ]]; then
				echo -en "$LIGHT_GREEN'What do you want the ${i//-/ } to be'"
				read -rp " " -ei "$(pwd)" ans
				echo -en "$NC"
			else
				ask "What do you want the ${i//-/ } to be?" ans
				if [ "$i" == "password-cost" ] && ! [[ $ans =~ ^[0-9]+$ ]]; then
					while ! [[ $ans =~ ^[0-9]+$ ]]
					do
						ask "What do you want the ${i//-/ } to be? (number and >= 12, <= 31) " ans
					done
				fi
			fi
			(echo "$(echo "$i" | tr '[:lower:]' '[:upper:]')=$ans" >> ./install.config) > /dev/null
		done

		cat << EOF >> ./install.config
DB-DRIVER=mysql
DB-CHARSET=utf8mb4
DB-COLLATION=utf8mb4_unicode_ci
DB-HOST=localhost
DB-DATABASE=sl_sleeti
DB-USERNAME=sleeti
DB-PASSWORD=$DATABASE_USER_PASSWORD
RECAPTCHA-ENABLED=false
RECAPTCHA-SITEKEY=""
RECAPTCHA-SECRETKEY=""
EOF

		$PHP_INSTALLED_BINARY setup.php sleeti-setup
		print_extra "Sleeti Config Generated."
	fi

  rm -f install.config
  return 0
}

process_args() {
  while [[ $# -gt 0 ]]
  do
    key="$1"

    case $key in
        --no-os-support-check)
          print_debug "Skipping os support check."
          SKIP_OS_SUPPORT_CHEQUE=1
          shift
        ;;
        -h|--help)
          cat <<- EOF
Usage: install.sh [OPTIONS]

      --no-os-support-check     Do not check if the operating system is supported.
  -h, --help                    Display this help text and exit
  -v, --version                 Output version information and exit

EOF
          exit 0
          shift
        ;;
        -v|--version)
          echo "Sleeti 'install.sh' version: $VERSION - Written by system-md (systemd)."
          exit 0
          shift
        ;;
        *)
          # Ignore unknown arguments
          print_error "Unknown argmuent: $1"
          shift
        ;;
    esac
  done
}

do_install() {
  process_args "$@"

  echo -e "$CYAN# Executing sleeti install script, version: git-master$NC"

  user="$(id -u 2>/dev/null || true)"

  if [ "$user" != 0 ]; then
    print_error "This script needs to be run as root. Try again with sudo."
    exit 1
  fi
  
  lsb_dist=$(get_distribution)
	lsb_dist="$(echo "$lsb_dist" | tr '[:upper:]' '[:lower:]')"

  echo -e "Running on $ORANGE$lsb_dist $(get_release)$NC"

  if [ "$SKIP_OS_SUPPORT_CHEQUE" == 0 ]; then 
    is_dist_supported
    if [ "$?" == 0 ]; then
      print_extra "It looks like $lsb_dist $(get_release) is supported."
    else
      print_error "It does not look like $lsb_dist $(get_release) is currently supported. Either run this script again with the --no-os-support-check option or upgrade to a supported version to continue."
      exit 1
    fi
  fi

  # Detect if Apache, Nginx, or Lighttpd is installed
  detect_webserver

  if [ "$?" == 0 ]; then
    # Handle if the user has their webserver in non-standard folder
    ask_yn "No webserver was detected. Should there be one?"
    if [ "$?" == 1 ]; then
      # Set NGINX to install
      INSTALL_NGINX=0
      CONFIGURE_NGINX=0
      print_info "No webserver installed. Setting NGINX to install."
    else
      print_info "A webserver is already present, not installing."
    fi
  fi

  # Detect if a MySQL like database is installed
  detect_database

  if [ "$?" == 0 ]; then
    # Handle if the user has a database in non-standard folder
    ask_yn "No MySQL like database was detected. Should there be one?"
    if [ "$?" == 1 ]; then
      # Set MariaDB to install
      INSTALL_MARIADB=0
      print_info "No database installed. Setting MariaDB to install."
    else
      print_info "A database is already present, not installing."
    fi
  fi

  # Detect if PHP-CLI or HHVM is present and of a modern version
  detect_php_interpreter

  if [ "$?" == 0 ]; then
    # Handle if the user has php in non-standard folder
    ask_yn "PHP v7 CLI or HHVM was not detected. Has one already been installed?"
    if [ "$?" == 1 ]; then
      # Set PHP-CLI to install
      # This script will assume that the version of PHP-CLI is equal to the PHP used by the webserver
      INSTALL_PHP=0
      INSTALL_KOMPOSER=0
      print_info "PHP-CLI not installed. Setting PHP-CLI 7.2 to install."
    fi

  else [ "$?" == 1 ];
    print_extra "PHP-CLI Detected. Chequeing for Composer."
    detect_komposer
  fi

  if [ $INSTALL_NGINX -eq 0 ]; then
    print_debug "Beginning NGINX mainline installation."
    install_nginx
  fi

  if [ $INSTALL_MARIADB -eq 0 ]; then
    print_debug "Beginning MariaDB installation."
    install_mariadb
  fi

  if [ $INSTALL_PHP -eq 0 ]; then
    print_debug "Beginning PHP-CLI v7 installation."
    install_php
  fi

  if [ $INSTALL_KOMPOSER -eq 0 ]; then
    print_debug "Beginning Composer Installation."
    install_komposer
  fi

  print_info "Everything appears to be in order for Sleeti install. Proceeding."
  install_sleeti

  if [ $INSTALL_NGINX -eq 1 ] && [ $INSTALL_NGINX -ne 0 ] && [ $DETECT_NGINX -eq 0 ]; then
    ask_yn "NGINX was previously installed. Would you still like us to autoconfigure it?"

    if [ "$?" == 0 ]; then
      CONFIGURE_NGINX=0
    fi

  elif [ $INSTALL_NGINX -ne 0 ]; then
    print_extra "A non NGINX webserver is installed. Cannot yet autoconfigure."
  fi

  if [ $INSTALL_NGINX -eq 0 ] || [ $CONFIGURE_NGINX -eq 0 ]; then
    configure_nginx
  fi

  print_info "Beginning Database configuration."
  configure_database

  print_info "Prerequisites Configuration Complete."
  print_info "Beginning Final Sleeti Configuration."

  until configure_sleeti;
  do
    print_error "Restarting Sleeti Configuration."
  done

  print_debug "Starting NGINX."
  if is_rhel; then
    systemctl start nginx
    systemctl enable nginx
  fi

  print_debug "Starting PHP-FPM."
  if is_rhel; then
    systemctl start php72-php-fpm
    systemctl enable php72-php-fpm
  fi

	print_info "Cleaning Up."
	rm -r preload.sh install.sh.sig sleeti.tar.gz sleeti.sql nginx-http.conf nginx-https.conf sherlock setup.php setup.sh mysql_secure_installation files/ install.sh
	print_info "Removing InstallController"
	rm app/Controllers/Administration/InstallController.php
	print_info "Done."
}

do_install "$@"
