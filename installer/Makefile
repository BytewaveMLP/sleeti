# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

files = install.sh preload.sh install.sh.sig sherlock setup.php mysql_secure_installation files/
tmpdir := $(shell mktemp -d)

.PHONY: build
build:
	gpg --armor --output install.sh.sig --detach-sign install.sh
	cp -r -t $(tmpdir) $(files)
	makeself.sh --current --tar-quietly $(tmpdir) setup.sh "Sleeti Installer" ./preload.sh