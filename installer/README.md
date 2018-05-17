## Install Procedure

(This setup file will install sleeti in whichever directory it is run)

1. Import installer GPG Key with `gpg --recv-keys 8830A567`. The key should be from "Aleik Coldésac (system-md) <system-md@users.noreply.github.com>".
2. Download the setup.sh file and run it. You may have to do chmod +x on the file.
3. Setup.sh will unpack the installer and verify the GPG signature.
4. If the gpg verification passes, inspect the `install.sh` file if you wish and then run it.
5. The install will continue from there.
6. Done.


### Notes
1) This installer leaves a copy of the adjusted mysql root password and application password in the root home directory. These files are purely for your convenience and can be deleted if need be. These files are only readable by the root user.

### Dev notes
1) The `preload.sh` file will delete all installer related files if it detects a change to the install.sh file without the corresponding update to install.sh.sig. This is good for end-users as it prevents execution if unauthorized changes have been made, but it will mess with developers who change the installer. To circumvent this, change the command called in the del function in `preload.sh` to a harmless command like `true`.
2) The cleanup function in `install.sh` will delete all install related files, including the InstallController file. Whilst this is fine for end users, if you are developing in those files, make a backup before running `install.sh` or the cleanup function therein.
