#!/usr/bin/env bash

# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

# This install script was written with apt based system in mind, but should work on other systems as well
# This script has been tested on Ubuntu 14.04 WSL, Ubuntu 16.04.

# Do not set -e

# For latest relese use: https://api.github.com/repos/BytewaveMLP/sleeti/tarball
DOWNLOAD_URL="https://github.com/BytewaveMLP/sleeti/archive/master.tar.gz"

INSTALL_NGINX=1
INSTALL_MARIADB=1
INSTALL_PHP=1
INSTALL_PHP_APACHE_MOD=1

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

get_distribution() {
	lsb_dist=""
	# Every system should have /etc/os-release
	if [ -r /etc/os-release ]; then
		lsb_dist="$(source /etc/os-release && echo "$ID")"
	fi
	# Returning an empty string here should be fine since the
	# case statements don't act unless you provide an actual value
	echo "$lsb_dist"
}

is_operatingsystem() {
  lsb_dist=$( get_distribution )
	lsb_dist="$(echo "$lsb_dist" | tr '[:upper:]' '[:lower:]')"

  if [ "$lsb_dist" == "$1" ]; then
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

is_debian() {
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
    cat /
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
  read -rp "(y/n)? " answer
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

  if is_debian || is_ubuntu; then
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

  if is_debian || is_ubuntu; then
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

  return 0
}

cheque_php_version() {
  $PHP_INSTALLED_BINARY -r 'exit(version_compare(PHP_VERSION_ID, 70000) >= 0 ? 0 : 1 );' > /dev/null 2>&1

  case "$?" in
    0)
      print_debug "PHP $($PHP_INSTALLED_BINARY -r 'echo PHP_VERSION;') is of adequate version."
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
    print_debug "PHP detected. Chequeing version."
    cheque_php_version
    return "$?"
  else
    print_debug "PHP not detected. Proceeding."
  fi

  return 0
}

install_nginx() {
  exit 1
}

install_mariadb() {
  exit 1
}

install_php() {
  exit 1
}

do_install() {
  echo -e "$CYAN# Executing sleeti install script, version: git-master$NC"

  user="$(id -u 2>/dev/null || true)"

  if [ "$user" != 0 ]; then
    print_error "This script needs to be run as root. Try again with sudo."
    exit 1
  fi
  
  lsb_dist=$( get_distribution )
	lsb_dist="$(echo "$lsb_dist" | tr '[:upper:]' '[:lower:]')"

  echo -e "Running on $ORANGE$lsb_dist$NC"

  is_dist_supported

  # Detect if Apache, Nginx, or Lighttpd is installed
  detect_webserver

  if [ "$?" == 0 ]; then
    # Handle if the user has their webserver in non-standard folder
    ask_yn "No webserver was detected. Should there be one?"
    if [ "$?" == 1 ]; then
      # Set NGINX to install
      INSTALL_NGINX=0
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
      # This script will assume what the version of PHP-CLI is equal to the PHP used by the webserver
      INSTALL_PHP=0
      print_info "PHP-CLI not installed. Setting PHP-CLI 7.2 to install."
    fi
  fi

  if [ $INSTALL_NGINX -eq 0 ]; then
    install_nginx
  fi

  # case "$lsb_dist" in 
  #   ubuntu|debian|raspbian)
  #     # Install ubuntu/debian prerequisites
  #     apt-get install -yqq software-properties-common lsb-release apt-transport-https curl
  #     add-apt-repository "deb https://nginx.org/packages/$(source /etc/os-release && echo "$ID")/ $(lsb_release -cs) nginx"
  #   ;;
  #   rhel|ol|centos)
  #     exit 1
  #   ;;
  #   sles)
  #     zypper addrepo -G -t yum -c 'http://nginx.org/packages/sles/12' nginx
  #   ;;
  #   *)
  #     echo -e "Unknown OS $ORANGE$lsb_dist$NC detected."
  #     print_error "Sorry, either your operating system is not currently supported by this install script, or we misdetected which operating system you are using."
  #     exit 1
  #  ;;
  # esac


  echo "$DOWNLOAD_URL"
}

do_install