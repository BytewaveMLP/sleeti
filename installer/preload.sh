#!/usr/bin/env bash

# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

# This install script was written with apt based systems in mind, but should work on other systems as well
# This script has been tested on Ubuntu 14.04 WSL, Ubuntu 16.04, Red Hat Enterprise Linux 7.4, Fedora Server 27, CentOS 7.4, and Arch Linux as of 13/03/2018.

# Do not set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

print_error() {
  printf "$RED%s\\n$NC" "$*" >&2
}

print_extra() {
  printf "$GREEN%s\\n$NC" "$*"
}

print_extra "Verifing installer GPG signature."
gpg --verify install.sh.sig

case "$?" in
  0)
    print_extra "Signatures OK."
    print_extra "You can now safely inspect the installer and then run it by executing ./install.sh"
    rm preload.sh setup.sh
    exit 0
  ;;
  *)
    print_error "Signatures do not match. Removing..."
    rm -r files/ install.sh install.sh.sig mysql_secure_installation preload.sh setup.sh setup.php sherlock
    print_extra "Try redownloading the installer and attempting again."
    exit 1
  ;;
esac

exit 0
