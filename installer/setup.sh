#!/bin/sh
# This script was generated using Makeself 2.3.1

ORIG_UMASK=`umask`
if test "n" = n; then
    umask 077
fi

CRCsum="519462251"
MD5="ac21352b3e63d5e4bdf123ad60bc60ae"
TMPROOT=${TMPDIR:=/tmp}
USER_PWD="$PWD"; export USER_PWD

label="Sleeti Installer"
script="./preload.sh"
scriptargs=""
licensetxt=''
helpheader=''
targetdir="."
filesizes="14470"
keep="y"
nooverwrite="n"
quiet="n"
accept="n"
nodiskspace="n"
export_conf="n"

print_cmd_arg=""
if type printf > /dev/null; then
    print_cmd="printf"
elif test -x /usr/ucb/echo; then
    print_cmd="/usr/ucb/echo"
else
    print_cmd="echo"
fi
	
if test -d /usr/xpg4/bin; then
    PATH=/usr/xpg4/bin:$PATH
    export PATH
fi

unset CDPATH

MS_Printf()
{
    $print_cmd $print_cmd_arg "$1"
}

MS_PrintLicense()
{
  if test x"$licensetxt" != x; then
    echo "$licensetxt"
    if test x"$accept" != xy; then
      while true
      do
        MS_Printf "Please type y to accept, n otherwise: "
        read yn
        if test x"$yn" = xn; then
          keep=n
          eval $finish; exit 1
          break;
        elif test x"$yn" = xy; then
          break;
        fi
      done
    fi
  fi
}

MS_diskspace()
{
	(
	df -kP "$1" | tail -1 | awk '{ if ($4 ~ /%/) {print $3} else {print $4} }'
	)
}

MS_dd()
{
    blocks=`expr $3 / 1024`
    bytes=`expr $3 % 1024`
    dd if="$1" ibs=$2 skip=1 obs=1024 conv=sync 2> /dev/null | \
    { test $blocks -gt 0 && dd ibs=1024 obs=1024 count=$blocks ; \
      test $bytes  -gt 0 && dd ibs=1 obs=1024 count=$bytes ; } 2> /dev/null
}

MS_dd_Progress()
{
    if test x"$noprogress" = xy; then
        MS_dd $@
        return $?
    fi
    file="$1"
    offset=$2
    length=$3
    pos=0
    bsize=4194304
    while test $bsize -gt $length; do
        bsize=`expr $bsize / 4`
    done
    blocks=`expr $length / $bsize`
    bytes=`expr $length % $bsize`
    (
        dd ibs=$offset skip=1 2>/dev/null
        pos=`expr $pos \+ $bsize`
        MS_Printf "     0%% " 1>&2
        if test $blocks -gt 0; then
            while test $pos -le $length; do
                dd bs=$bsize count=1 2>/dev/null
                pcent=`expr $length / 100`
                pcent=`expr $pos / $pcent`
                if test $pcent -lt 100; then
                    MS_Printf "\b\b\b\b\b\b\b" 1>&2
                    if test $pcent -lt 10; then
                        MS_Printf "    $pcent%% " 1>&2
                    else
                        MS_Printf "   $pcent%% " 1>&2
                    fi
                fi
                pos=`expr $pos \+ $bsize`
            done
        fi
        if test $bytes -gt 0; then
            dd bs=$bytes count=1 2>/dev/null
        fi
        MS_Printf "\b\b\b\b\b\b\b" 1>&2
        MS_Printf " 100%%  " 1>&2
    ) < "$file"
}

MS_Help()
{
    cat << EOH >&2
${helpheader}Makeself version 2.3.1
 1) Getting help or info about $0 :
  $0 --help   Print this message
  $0 --info   Print embedded info : title, default target directory, embedded script ...
  $0 --lsm    Print embedded lsm entry (or no LSM)
  $0 --list   Print the list of files in the archive
  $0 --check  Checks integrity of the archive

 2) Running $0 :
  $0 [options] [--] [additional arguments to embedded script]
  with following options (in that order)
  --confirm             Ask before running embedded script
  --quiet		Do not print anything except error messages
  --accept              Accept the license
  --noexec              Do not run embedded script
  --keep                Do not erase target directory after running
			the embedded script
  --noprogress          Do not show the progress during the decompression
  --nox11               Do not spawn an xterm
  --nochown             Do not give the extracted files to the current user
  --nodiskspace         Do not check for available disk space
  --target dir          Extract directly to a target directory
                        directory path can be either absolute or relative
  --tar arg1 [arg2 ...] Access the contents of the archive through the tar command
  --                    Following arguments will be passed to the embedded script
EOH
}

MS_Check()
{
    OLD_PATH="$PATH"
    PATH=${GUESS_MD5_PATH:-"$OLD_PATH:/bin:/usr/bin:/sbin:/usr/local/ssl/bin:/usr/local/bin:/opt/openssl/bin"}
	MD5_ARG=""
    MD5_PATH=`exec <&- 2>&-; which md5sum || command -v md5sum || type md5sum`
    test -x "$MD5_PATH" || MD5_PATH=`exec <&- 2>&-; which md5 || command -v md5 || type md5`
	test -x "$MD5_PATH" || MD5_PATH=`exec <&- 2>&-; which digest || command -v digest || type digest`
    PATH="$OLD_PATH"

    if test x"$quiet" = xn; then
		MS_Printf "Verifying archive integrity..."
    fi
    offset=`head -n 555 "$1" | wc -c | tr -d " "`
    verb=$2
    i=1
    for s in $filesizes
    do
		crc=`echo $CRCsum | cut -d" " -f$i`
		if test -x "$MD5_PATH"; then
			if test x"`basename $MD5_PATH`" = xdigest; then
				MD5_ARG="-a md5"
			fi
			md5=`echo $MD5 | cut -d" " -f$i`
			if test x"$md5" = x00000000000000000000000000000000; then
				test x"$verb" = xy && echo " $1 does not contain an embedded MD5 checksum." >&2
			else
				md5sum=`MS_dd_Progress "$1" $offset $s | eval "$MD5_PATH $MD5_ARG" | cut -b-32`;
				if test x"$md5sum" != x"$md5"; then
					echo "Error in MD5 checksums: $md5sum is different from $md5" >&2
					exit 2
				else
					test x"$verb" = xy && MS_Printf " MD5 checksums are OK." >&2
				fi
				crc="0000000000"; verb=n
			fi
		fi
		if test x"$crc" = x0000000000; then
			test x"$verb" = xy && echo " $1 does not contain a CRC checksum." >&2
		else
			sum1=`MS_dd_Progress "$1" $offset $s | CMD_ENV=xpg4 cksum | awk '{print $1}'`
			if test x"$sum1" = x"$crc"; then
				test x"$verb" = xy && MS_Printf " CRC checksums are OK." >&2
			else
				echo "Error in checksums: $sum1 is different from $crc" >&2
				exit 2;
			fi
		fi
		i=`expr $i + 1`
		offset=`expr $offset + $s`
    done
    if test x"$quiet" = xn; then
		echo " All good."
    fi
}

UnTAR()
{
    if test x"$quiet" = xn; then
		tar $1vf -  2>&1 || { echo " ... Extraction failed." > /dev/tty; kill -15 $$; }
    else
		tar $1f -  2>&1 || { echo Extraction failed. > /dev/tty; kill -15 $$; }
    fi
}

finish=true
xterm_loop=
noprogress=n
nox11=n
copy=none
ownership=y
verbose=n

initargs="$@"

while true
do
    case "$1" in
    -h | --help)
	MS_Help
	exit 0
	;;
    -q | --quiet)
	quiet=y
	noprogress=y
	shift
	;;
	--accept)
	accept=y
	shift
	;;
    --info)
	echo Identification: "$label"
	echo Target directory: "$targetdir"
	echo Uncompressed size: 80 KB
	echo Compression: gzip
	echo Date of packaging: Thu May 17 15:39:30 CDT 2018
	echo Built with Makeself version 2.3.1 on 
	echo Build command was: "/usr/local/bin/makeself.sh \\
    \"--current\" \\
    \"--tar-quietly\" \\
    \"/tmp/tmp.LS8NqLeXLG\" \\
    \"setup.sh\" \\
    \"Sleeti Installer\" \\
    \"./preload.sh\""
	if test x"$script" != x; then
	    echo Script run after extraction:
	    echo "    " $script $scriptargs
	fi
	if test x"" = xcopy; then
		echo "Archive will copy itself to a temporary location"
	fi
	if test x"n" = xy; then
		echo "Root permissions required for extraction"
	fi
	if test x"y" = xy; then
	    echo "directory $targetdir is permanent"
	else
	    echo "$targetdir will be removed after extraction"
	fi
	exit 0
	;;
    --dumpconf)
	echo LABEL=\"$label\"
	echo SCRIPT=\"$script\"
	echo SCRIPTARGS=\"$scriptargs\"
	echo archdirname=\".\"
	echo KEEP=y
	echo NOOVERWRITE=n
	echo COMPRESS=gzip
	echo filesizes=\"$filesizes\"
	echo CRCsum=\"$CRCsum\"
	echo MD5sum=\"$MD5\"
	echo OLDUSIZE=80
	echo OLDSKIP=556
	exit 0
	;;
    --lsm)
cat << EOLSM
No LSM.
EOLSM
	exit 0
	;;
    --list)
	echo Target directory: $targetdir
	offset=`head -n 555 "$0" | wc -c | tr -d " "`
	for s in $filesizes
	do
	    MS_dd "$0" $offset $s | eval "gzip -cd" | UnTAR t
	    offset=`expr $offset + $s`
	done
	exit 0
	;;
	--tar)
	offset=`head -n 555 "$0" | wc -c | tr -d " "`
	arg1="$2"
    if ! shift 2; then MS_Help; exit 1; fi
	for s in $filesizes
	do
	    MS_dd "$0" $offset $s | eval "gzip -cd" | tar "$arg1" - "$@"
	    offset=`expr $offset + $s`
	done
	exit 0
	;;
    --check)
	MS_Check "$0" y
	exit 0
	;;
    --confirm)
	verbose=y
	shift
	;;
	--noexec)
	script=""
	shift
	;;
    --keep)
	keep=y
	shift
	;;
    --target)
	keep=y
	targetdir=${2:-.}
    if ! shift 2; then MS_Help; exit 1; fi
	;;
    --noprogress)
	noprogress=y
	shift
	;;
    --nox11)
	nox11=y
	shift
	;;
    --nochown)
	ownership=n
	shift
	;;
    --nodiskspace)
	nodiskspace=y
	shift
	;;
    --xwin)
	if test "n" = n; then
		finish="echo Press Return to close this window...; read junk"
	fi
	xterm_loop=1
	shift
	;;
    --phase2)
	copy=phase2
	shift
	;;
    --)
	shift
	break ;;
    -*)
	echo Unrecognized flag : "$1" >&2
	MS_Help
	exit 1
	;;
    *)
	break ;;
    esac
done

if test x"$quiet" = xy -a x"$verbose" = xy; then
	echo Cannot be verbose and quiet at the same time. >&2
	exit 1
fi

if test x"n" = xy -a `id -u` -ne 0; then
	echo "Administrative privileges required for this archive (use su or sudo)" >&2
	exit 1	
fi

if test x"$copy" \!= xphase2; then
    MS_PrintLicense
fi

case "$copy" in
copy)
    tmpdir=$TMPROOT/makeself.$RANDOM.`date +"%y%m%d%H%M%S"`.$$
    mkdir "$tmpdir" || {
	echo "Could not create temporary directory $tmpdir" >&2
	exit 1
    }
    SCRIPT_COPY="$tmpdir/makeself"
    echo "Copying to a temporary location..." >&2
    cp "$0" "$SCRIPT_COPY"
    chmod +x "$SCRIPT_COPY"
    cd "$TMPROOT"
    exec "$SCRIPT_COPY" --phase2 -- $initargs
    ;;
phase2)
    finish="$finish ; rm -rf `dirname $0`"
    ;;
esac

if test x"$nox11" = xn; then
    if tty -s; then                 # Do we have a terminal?
	:
    else
        if test x"$DISPLAY" != x -a x"$xterm_loop" = x; then  # No, but do we have X?
            if xset q > /dev/null 2>&1; then # Check for valid DISPLAY variable
                GUESS_XTERMS="xterm gnome-terminal rxvt dtterm eterm Eterm xfce4-terminal lxterminal kvt konsole aterm terminology"
                for a in $GUESS_XTERMS; do
                    if type $a >/dev/null 2>&1; then
                        XTERM=$a
                        break
                    fi
                done
                chmod a+x $0 || echo Please add execution rights on $0
                if test `echo "$0" | cut -c1` = "/"; then # Spawn a terminal!
                    exec $XTERM -title "$label" -e "$0" --xwin "$initargs"
                else
                    exec $XTERM -title "$label" -e "./$0" --xwin "$initargs"
                fi
            fi
        fi
    fi
fi

if test x"$targetdir" = x.; then
    tmpdir="."
else
    if test x"$keep" = xy; then
	if test x"$nooverwrite" = xy && test -d "$targetdir"; then
            echo "Target directory $targetdir already exists, aborting." >&2
            exit 1
	fi
	if test x"$quiet" = xn; then
	    echo "Creating directory $targetdir" >&2
	fi
	tmpdir="$targetdir"
	dashp="-p"
    else
	tmpdir="$TMPROOT/selfgz$$$RANDOM"
	dashp=""
    fi
    mkdir $dashp $tmpdir || {
	echo 'Cannot create target directory' $tmpdir >&2
	echo 'You should try option --target dir' >&2
	eval $finish
	exit 1
    }
fi

location="`pwd`"
if test x"$SETUP_NOCHECK" != x1; then
    MS_Check "$0"
fi
offset=`head -n 555 "$0" | wc -c | tr -d " "`

if test x"$verbose" = xy; then
	MS_Printf "About to extract 80 KB in $tmpdir ... Proceed ? [Y/n] "
	read yn
	if test x"$yn" = xn; then
		eval $finish; exit 1
	fi
fi

if test x"$quiet" = xn; then
	MS_Printf "Uncompressing $label"
fi
res=3
if test x"$keep" = xn; then
    trap 'echo Signal caught, cleaning up >&2; cd $TMPROOT; /bin/rm -rf $tmpdir; eval $finish; exit 15' 1 2 3 15
fi

if test x"$nodiskspace" = xn; then
    leftspace=`MS_diskspace $tmpdir`
    if test -n "$leftspace"; then
        if test "$leftspace" -lt 80; then
            echo
            echo "Not enough space left in "`dirname $tmpdir`" ($leftspace KB) to decompress $0 (80 KB)" >&2
            echo "Use --nodiskspace option to skip this check and proceed anyway" >&2
            if test x"$keep" = xn; then
                echo "Consider setting TMPDIR to a directory with more free space."
            fi
            eval $finish; exit 1
        fi
    fi
fi

for s in $filesizes
do
    if MS_dd_Progress "$0" $offset $s | eval "gzip -cd" | ( cd "$tmpdir"; umask $ORIG_UMASK ; UnTAR xp ) 1>/dev/null; then
		if test x"$ownership" = xy; then
			(cd "$tmpdir"; chown -R `id -u` .;  chgrp -R `id -g` .)
		fi
    else
		echo >&2
		echo "Unable to decompress $0" >&2
		eval $finish; exit 1
    fi
    offset=`expr $offset + $s`
done
if test x"$quiet" = xn; then
	echo
fi

cd "$tmpdir"
res=0
if test x"$script" != x; then
    if test x"$export_conf" = x"y"; then
        MS_BUNDLE="$0"
        MS_LABEL="$label"
        MS_SCRIPT="$script"
        MS_SCRIPTARGS="$scriptargs"
        MS_ARCHDIRNAME="$archdirname"
        MS_KEEP="$KEEP"
        MS_NOOVERWRITE="$NOOVERWRITE"
        MS_COMPRESS="$COMPRESS"
        export MS_BUNDLE MS_LABEL MS_SCRIPT MS_SCRIPTARGS
        export MS_ARCHDIRNAME MS_KEEP MS_NOOVERWRITE MS_COMPRESS
    fi

    if test x"$verbose" = x"y"; then
		MS_Printf "OK to execute: $script $scriptargs $* ? [Y/n] "
		read yn
		if test x"$yn" = x -o x"$yn" = xy -o x"$yn" = xY; then
			eval "\"$script\" $scriptargs \"\$@\""; res=$?;
		fi
    else
		eval "\"$script\" $scriptargs \"\$@\""; res=$?
    fi
    if test "$res" -ne 0; then
		test x"$verbose" = xy && echo "The program '$script' returned an error code ($res)" >&2
    fi
fi
if test x"$keep" = xn; then
    cd $TMPROOT
    /bin/rm -rf $tmpdir
fi
eval $finish; exit $res
� ���Z�[��H�ϯ��8��$q��1��#���d�9:�Z����ouKⰱ33;��y/�ǀZ�ߪ�����j%�W��Õ�y�-�[���JH���t.��S�x���W(��\>�T�W��~��_�Jrd�]��>����g�ѿ�gS�G�ri����O�޼憆F{{��5���ck�W\Ӱ��(H�u4S]�!H%SxN�3B�Cӎ�D-4T�\���"֮�t�x�1�=ñ���b��cנXF���&�(el{���RB���C���#Ƣ�{�-����\]83�tf'(���R]�R	!�j�	�)�#����	�U�W��ϛ8n>�'mg�M3�9�9�I�1L�V=�Ŝ ��Br�Y�!���#�7=��I�*�N�ę't'a$F��'�K�'���&*%�Q¤"J$���m��m�|�X0\b���
\�ҹl*A��Z��C�1�n�H 9�AK.&�"A�M�A�c��f`i����.b4U]*1��pAŠK"�C)�.�m��dU`���jMMj��w�[a�Qd� ?[b�^\����]Hr[jS�\2�Jd����JC�ɍR��贋:�(���F�z}_��ƥ�k\J�JT&KMIT�b.�'sԞ:�-��"��;`3���A��01��Y�n���p�Ȓx�JX:%��1S���� %&>���3P�;2M�F�$�0��k��x�b�q�X,�{�"z��O����-F����ͷ5f��󴤥�*Gaqd���CM�V8�X?<B���4y�۪�Q�ܰ�Ш̺	�am�кe}�j�Ϡ�1B���}Gy�GE���vG�G��s*d;��j)�4�7{���FR ���,]>�Ӻ(�<�9�g�VX����C��Ǭ�����l��q���gܠ��'J��T��3�*�;7�z[͡[���7;�-Us�s�l���L8����kΙW0��{ ������#�����3��js.{8X?ܰ=p&�����T䰧qA݄�M���x�����O�8�L(؝��q�'֭8:� (���q:�q�����>ͦkq�՝��9�>Gϑ���[���jص������/��8%-P1��~<1�ض=��Vvi�ǐ��z?1LyM.�v`����2C����`P��f�	2<Z�a 81u��2]gls<bȿ���&�k'�Qͷ?���f�&���V�����u�(�������_�����s�����`�����?-|2C�=���2�p՛'R���6s��8�0t� ��� i�r���B�n���U��R����8��/b��,���z�}7�X*Sg(�p�����6�<2���o�_�.s����.�٘��7\AݝS�;�`呫~�$r��R�ܬ�������f6��_5ڏ�Exa"=�[���N����U���S�C�t��T�V�R�� ���`����?]<�R��J��q7+,6��
�$*~Ba��{�߂b�-V��P�����|��7o��'B� *s����d���0�P]���!ǭn?=/�0�*�5
����e{�>�L�����艫�-!}�d�-�ֺ��x�Y���Y���-.ܠ���:E$I&O�x�@���Y�U������$<��x�J��CFEX���Em��f��Ӆ��"����\�����j��f���_l���X�A��o��������T.'|�����Tk�Q��EJ��{}Y
,�2pzT��n̈́X�x��J�X�b����an��R�"�c�Ѻ�����.w;:��沗wisv�I��Y����رۿ��*},7���\~aj�/��EΚ�&F��p�u�㫷W�]���o���N9�u�>��nLэ˪ees��e{,`5�areLZs[�t_�����痚T�K�6�c�5K�y�[4>�����l���QO���	�*}�
ް�[e����*�+����]mv۬uN��|,��!�s�+�K�&�r˚ar_f����ռ�q���/��RX�>�[���:�'�x:�g{3a�NM�t)��8y��ۆ=<�ʜ���Ek��[�M�b!�/s�8Kχ�Ji*�٬���%�ͫ�^�No���1��T���S�o�*�T~�I����ه�~%7s�e�d.�Խ�c��M�Y���:��W�_׼ԭ�9��V=�MF_�b�t�®XY���zw����]��w����6i������3��B�k�i�C��"�G��_�ƽ~��S�k�)��}�e���1�Y&�
�I�Uc�������r�q����n��<���Y���w��^?�J�X��Ju�G��Ԟ9����C+��0o�ϧ+ͷ���W�h���Ѹ[��n��j�R|��v�j��U����ǋY��["ƚ���ۼ32�ݨ[[hB�+�ڗ�3�]�5(����%�b�|��@�S����,�{���i!�����O���a{��*,�边3]��x�C��x>�2,��qU�d)�qa�C�:���z/�D�Y;�s��=��l�|�:cW������4%3�hDw����2;F�"L7rB2l'}	0P�۰\f��4e�P��G5lcW5Q�������m�v~���L�&?��Uʁr���2�?G�R?a�@�{�fX��+��l[���}�!�)��o� �E��z��Cb��eYl���Y����x�$Ú�4� <���-�#�/��:�K�f�wM3e�F�-)
�vd$��(��~S�Q�/w;��DH�8�ս ��6V��a���נ��:��3j�0D+:R���u �j��%Q�"<��J/��9Kz�S��$�J?'�ak����j6A�
4�#����㞠�C<Z�%">%|BH�B}E�3�?1�|�d[/{�N5�US�h3f� ��^Q<������Վ����)�9b1�z�p�@؇��D	��W���������=8�)[o���� �`o@[�*!s��W;{��!�'�Oh_�z`Q��#WPq��0~ ď΃3hY2���AP����l:�>�B���ۦ%���l�������d*�n�	TK�B�HrQ��.=�MX˙�j;��r|2 ��d%�u+RS�I�*wZ�Г�&z_�d�f��b<~��ftK�!��a&��G����{��:�2jwz��q��	�OP\H�<�	��P`�x�/�@�*xz��3��E���"�FIWU+,����7�[a�n�ԖF^�>;_�V�#�n>}��8�눇�| ����C�f_���ܸl4���<'ݿ����A����o��/A���}K�{sc����?%d�������ǆ�(;�(Io���3�'�_&��}����u��M5й1N���@j����~x��ۢ�!D9�d2iD����΃��6`)�����i���C��- �������@�.;� !c0c��Qʒ���Q�O�>������zЀ`B���jF�L ����,��v%ϰ0��P�� 4��1	3!�Z!듩J�uG�k���hƔ�Bq�\�K	Y�����D��J(u1}�)l=R��3�Vx��6/��Y��m�u��5���$ʥr:(�ԟ>��Ӿos���'�}�$Qo���j��l^��K��v�^�uYlI�fC,��&�׭J��\^��L�<�>�A�vi22`������t2�&���{���������Lz�f`����їb�a$�{�5����z{��&X�;�����A�K���%�Ugx�<��:�Ŵ�M���B,mk��c�ꎥ69�?g��A.��Sv������.l@ �>8ĭ:x`����� �-��O��.�b��@���J<ml��4h��M�=r���Ǭ��!�<>:�uC�QԆ�5���>�,7��=�C���@w4ߢ��:8X�d�1�gz#h��Ot�	FZ�a�3��@Ho���%R5��HN����鄪_�R'(��ic0P3	v���S�ِ?�	`��5z���g�~��B�\���M���k3���mB�=)p�;\�N9@��@���[��#��Fю��ݾ�_���S{��������?�R�'�|}_����?l�\	����G\�� ���n�o�`ZΝa�j��(���	�%Q
�j�eZ�&ۃ��6)�4�Por�R4C�=��x
�z��2V@9�c 9 �%W�-<z?�ң�:C1:*��P=��g��X�OX�&L|��3�^�XդX�4w����2x$8	E�F���ա��%)�Of��0Xވ8�j�7 �w���I��^i��JrPr��V��8�?	ߩ�y%�W��*��?��g�/�mP�=��|F���FWN��L��	[��C\ȱC� ސʒq@��!�F�:"��d�5 xu0�0o��Ii��ܖ�D�u��tՂ�.5{���M�@{��t?x��p��#���
�J�K#��Н?�	gL� c��#�f1=����j��N�����W�\<uWZzx�j5�ai!����t޷��2����~�����-}{͘a΂��I�J���c�����c�F[���]k�����%��R�(�ֻw���P+w��F�/K+��ԓʽ�m,��N�F�A֧����l�IL�hte���4���һ�T�c1`e2"U�F[���qXn@�RS,_��t�#��xL�*�
j�$��E)(��b�&���PVj�7J2PB3�͍�,������4!j�����<oѾ���B�^P}ͤ0�B�W{O��Ƒ\�O1�� �q�A}�(C
M�ϒȐ�9
������#��v��٩�A�$�R�J�_^��F鏙ٙ� �勯�*[�ݙ������j�Q}f���񻇺��p����mF��ȷ��_􌢈��]�x(�a�l߻!�v:�K�|���g���w���,�H��5�Sê���dꅴ?'���ro�^�#��Tj2$��Z�����,W̘�r�Ir+�˗)�l��:l�ёMJ!/��@!��
��	��M1Q�E�lm4�n�9���|̨�����s�����&<g�@��((���Nj�.ܱ?ZXï��(80�l	�����!
���:�"~�1�~\d���4P������Se��氂b�E�D�����r&�y`%�1F�ׁ�=����:�)�f�f[�D�D,��$��`J���JM�U��|�z�=���*�Mn�w�lpv\y�1L��{�rXK�U[bn�: ��Z<'��'_5���؜����SH�P� �4[����p��zp��r�Xb=�|8q9T.�\�~�8	Aݵ�3Ih�D��)j�ˠ�J��G�������ֳgj�����Ւ7W���". �I0��]>a4)J���ʣ9�� �_�����(N�+0��f�x�R��,��O/Cw��F�aO�|���/�?\��AN� ���c�Ӆ��1��y�B�"�H�]O���r�H���`!@|7S�G���n[��E�@��<���[/�����B �bI7�]�GBٞO��`��n>Z|^�����e���r�\c�M�|��F�
��=9îP2F�i���<1�r[�2ȶ�����v8�ҙ���y�B��Q~���<�1�����R���]����J����>��E�Μ��i�����\"���g{�T��bH�0��n+�C.�d��ܴ���O�b�5��w;�d2�5�躿P
�6�h��R�-ʴZ�`��SQ�-�@��p��l3��Ff�iQ���nm������w"=)䋘o�e�f��
�m��MJ�����N���D'>K+��ՒV�U'ͮ��ֵM�r*�[Q�ye�9�`^�;T!��N�q͢�f��������R8#�~�c�꘴����w�� $��d��Boz����%�L���W��W��0F���Ƙ]��m������th�Ros��x�싵��~��^dp�1�E����2
=�K�:,�R�Y>�Vr�tD�!�`��������
0��E�50�M�U���S�)��12EQ}%�|2�Pt��h�:Ee�|F�r�l솾;<Ϯ����4�e����).�e�g�(EoRn��8��W�<��8kJb��'�����1�p�Ɨӫ��b�bM�+>]^3w��iu%�F뮰���w��. }��зVC�3a
���d)@�;%�&&wú�a�!w�<#��+Yp1'G�YM�v8�����^a�`oS<� ��x�-�����Ϫ非�6/_h����bK@`�,��R��x<���f�F��U��d���$gK�Zl����dJ0���=c<�I��C�\l��7�����: �l�7���۩�R�5�	�rZ�ML�i/+eXj b��K��]����(�i�Γ���ݤ�5��$Jt7H��}ܕU�ڨ�9����
�3��$�Og��CZgH�T�ZJט��F}+�����<���R��94�a��xj�A��R���̹�4����A��v�!:�g�9h�����3�L��le��Cr�p�_b"\� G
@��aL#����ҡD
���?�#��?*8 #0�\�c0��)�����I���CuA��T�(WV3�Z���L��	h9g}$��EE�*ǋ�w4\9 ���#	0�ze?f����9����@c8���c���ts�^a"�1���2EiE���5�)'���3<�� ��)Ѥ�X>D`��|&�sg+.I;��M9�
�k'�ˀv�jQî���_;�{��
&�5�F���7>�N��Y	ٷ��<��h-��wd�^�d�3��^���6+�sr�&t�Y��^��;�G�dC�h�
�6"^�a�e�M�V`C�ʓ�e�?̽� ���V�������S�pN����C1r�:�^�I���m+��Zv�!c�wk)�(dMe᭔�L�H�<�1���0^]#���o�a���W-Ѻ�o?y�d����G�{��Kv��ڲcL}�A���K(+�P�� E6ۭ��� �q���HRH���B�)*�q�JLHz�v�=�����Α�K+�N��x:�Z�Ƙ�L@�vC��0��$��*w�0��n,��C�3-[�;�	�
uEf��I
vE�TO�Νf���J��	|��Y�ol�e`��Eb�Ḧ́��I�/�̪r�O��f#���]����^vI��L�L����5�bL�;�U�`�bK���j�݁�*�
d�[���`�d��f��Ԉ�$��/��=���i��to=y��dw����z�j�;�r��N��ȝ���;lެ9	����&��97�"�V+#.2+�i��4AMtl�@��q�DZ��\���?՛�D�q��蓿�É�9>GAM���i!_����5:EV5G�S@�`�c�3��C"�6�-{`��L��1�ƃp1%�s;՗��,'�N?��>��1�u�x�Bz�q��8��O���j?���_��Ĵ��%R�����2�N�ZY��4��Vߩ�N�)ۀ��%�a3t�@J �^:�1���hM�	�GcI1?ozSoD�Ө�'O1j\M�����uAl3I_JR�����9T�b�K��`|�Iv4X��SdH�+H�ЦC��ݗ�pF3�4�����U���x��þ:�ܡ�]����ƯKʙHdr�g�f%�#-j~���F.y��WSh��M�t6�8��������R�%��C^��\gJ���$�SXQ@���S�K��Ɨ��k��)��x��/{�}� ��8Us��Zd-��`/�,���x�+R��Ĉ����k)��Z8�f�Ck�
��=J��"��4�տ�1E�`޲��{'	��)r��,[_��'6�,�Ǡtt��G�>Z�r�X���G�� ��0������u�	K�N��; Y/�~\ۏ���#����6���N��0��H;���;���i��zE�&jUΒ��ӭc��z���q��<�n���w)&�<�<�́�3��=!E�1�<��J�g��5ڼ�v�����A����8UՆ�8����{���({~� E`!���>�֙$�(�I��Vznh^ʛ ��3n�����S��=b6��,��'f¢#�J/?ȬT�p�T�:o��*h�h�w�kQ���G$MdSt"����2\����M	D��K �E p�h<z��k�#�	�ޫ����4���0U`�&�a0�c�]�)��a:@�E�\8�����
?�%�20@�'�T�_���Z
4����W�?p9����EF�+o*j7�����|�R�ݞЉW]@�����
���Q�fva!R{�?�(qT.���q�>�Y�Z6h}z3y�eVLB�n��B��S1���_�)�(�!'�Y��+NX�;�v�&�y!j��Hʄ��<eH�uq��C�*���&Q���?��w�)�B
[&@�xy����tu-�F��B n<Ԙm���p��ir��}4O�4�ٿ��űv���X`�z��L���:�̃Ƚ�FG�:w�z����3���`�K�YFH����f��K��`C�Δ|�jJ`�hBL��|'� k�2v��v�19:�@���j��b&%���NO[\�3Ӆ��"XL<*�U��+��#ӝE]�{���Ca����)=���;�M�zW�	���܊�"�6���L
w���.��3q�{w'���A��cn�����2���k�� LVL4iq��z��)ML������d�X$�����0r��~�^L��+t�aB˲�����)&Op�+w�1�Ĵ>�����Dsn�~+�����Ӈ-�,���G@l�E� 2`��F�4�#�!lM�K+��� �-���ejY5���x�y���BJ��WX�A1g~��	t��(�-��%�,`��P�#��f�I�a�\���q�W8�w��  ���]�fo$�I6`.��[��\z���������п��=��3�!�0�1rhQw`��GDeǽ@Y���!dK&��8�ȕ������F] s'5���	U2WCŔ�RA2�VѺNh|k:�t�b��̟�8\o������>����G{��
��QY �c��_�Ř�2Uۤ��i����ş?�j�P79�Z��e��f�\��\S*��9L���V)B�׃�d8��lQ$ʞr��1lf���G���BF%�k)�@��Ĥe��8�����TR������{�c.q$D����f�t�Y/�ܹ�O�U9�2|J_jb�V� $)����C:���N�F�Ď�O��C��moFz�e��]��G�p�	1�K:ȶ�Xѣ8$lQ�'�Yd���ml�&��ZVSM<�����t���o�
��F�D��d�r��v5�?�G -M�=�޳h
�7#7��)������g�*��:�c�:�I��E�U%����H����F.Ѹ�d	4�9��L8�5"K!�U�V�m�mCB˻� �����+�
�]sf'�[������Hk�cPc�q��]��vΥ�7��#�O��i�����R�w�d6lS�6)C_3���(|�F��̘4�L������L��[꒓>�哗��kR��W�uEӤ|�M�H�}E���B�4$��8-���N�8�3Cnl�%gqU��ƽW�#>2�YV�?ᣇ�{T#���`V���{c�Pχ�d���@�7H`m`��$���BwD)�R�R��s �y� ���H���3���4v���G�����K��D;������������7�ڃ:_<���������$b��A�CT��U\������~�����,c��
�>�H������t�4�3���e�8���S3�3�0����j'�$m _��Xu���.=ᄃq�קj% �+�X%$qY�an�	N��iA5�u%U_�M��.'��M΋�_d����<���,��G޶�D3|���&�6�3b�&Go>O��C��W��t_���T�Ή��@A�&0���vX�[1�x#O"?q�g�j�X�]���rf��8�9����]uP�fu���J>���H��%��	�¶����_�mz��.�tX?��0~R�c�d���Wt3�Q]f]�3ew>`�,�m�@�5�j�D){_�Ǫ�T��}8-n�" �7ݔ8�ͷ��&@�3wi-@}��F/]?xQ^��;�6F ύ��e�NKQ�F��t��,�9�d�"0�������b���8�|���}��K�e�|��Qi��k��c��+?�.fcG�n�O�m��Q�-��� �gK���	`2�oH���;t������f�A��Pdf��8Uj��N�����AR55��Q�o��	Y=����//��s&6Y?R���po�2y�E�I���(����nN�DM\6sc+S5��N0vߘ��OvBKmY{Չt���ǜ�ES:�H*���@%>�D�6�`�������D�E��[VI�D�/Mw��U*"��9�X���T���rUI#�F�p¹ʟ�f�i��X��εa��r<ݰӛa��Am`���P3������)�[Ky)�T�	�;���/�Oa���b�{�#y�%��s-4E-���i�z�9$�[���xؖ����O��҉�t^�^2�6��>�X%Nj��	����w|���q�����˝�n�3�]|5>D�_������e�x̠?����Ó^G߸K@��h��jE;~O�l�P:���� )���������������S.�/���ݞ|����~��LE=�=�a�d$�kk�S�c�=JRog�D��R�g�ǵ�	����
ne��v3-�ȑi:�UO��J���f':1��ֆk���kt�_Ļ�:����������qz��6r/�m_�#N���N�T�Pf�苼��_�|7J*W��t��(m
�Id����t�6/ThCNsc��G���9&���<΋��mᘯe�0�����k���u�V	�ɓ՘�U�Ƕ���2���[\�/n���8jL|���΃� �,N�XEyS���s(�˫ڕ��H��D��ˮ���x��]�8�$fJ���Kѽ�s�sl�{��l0��$��ˠ����[ύ4j(�瞕\K������`^ex�0���p`�Q����&���r��0O����i�5)��A ZW�v'�VQN�$[����� ��{u�m���h2��X7�C��d|an~\�rhd���t3��|c|Q�e�@>;�	��$�1t-�(�! .�Ǳ4�W��}��	}ݦx�[Û،Ώ�E#�̙YZ���x	�V:)��*��0��E%7�2b)����P�UJ���Q7#�DA'<9#g/L"�Y1���|�f��ŏ��DN+3W��k�ZI�,DCU%�J6��~m�%�.�M��Tҥ�qv'B$�.�C�>�u�]��B�����8���t�@�ۡr���MjDӰW��I�OR����˗����51��`�7ֹ�h��,;��]GU�CLy�
Y}���t3\����h+��F;�9�*�c#ٺ��\�����$�]�#Y w������x��˒��4X�d��y��u9re�'3I���H�Gi���:vg�3�S�<�eY"�x����9{�muF����h~�ON�q:�����Y-��nMI��SwFc�Y���=����d�.��.�N1VM�L8�'N,:���ެZ�+m�]O~�h�of��Z�e���F*�;>hA�͜:�a�I'�J�3[P1��ܭ�@(F�԰�}���!1�6�O.���豒�<���3X����+J�☀L�z斢Lbc�y�@e����Yk�S���m�'�����K�?��L���%;2T9YT5���42qr<��F���"�p�(�lE������Yg���M16��	>0v��o�j�:�9��g��Į	<\������O��ES�#T��_��Mw��Fr����������2xN�%���wQsǈ��T-:�h�����ʶ��=�wj�������/s�wkk�����߭'��������5�����{ս�K��ۭ����C��;�*�"�+�W��6�u.�����{���ɩ\�}k�(��^���H,ʥR"��Lh��2��F9]@�]0�|r������p�ˋP����=���d$/���d�j�/Y{�;�V������P�>�ʊKJ]H�/��F������N�1������)r�0�S"y����?�ő�_�?����o�2��V�Q��X��Oݯ�����/P�/5<(�?˲`~�u��y����3�����_a�g�~e ����I�����p�����W�����	����ٟކ~��Kۏ���=:q��d�H�r�*�S-�:�N`L\w�����s7ʷ��w�W�����4�����e.4�|����U��r��@m^�1ͼW���HQ�>�֟�zNnXS&�T��wp��6e�$:xh}�tF�@}��#�����3|5���-ޖ����'�;�p��ӗ�N	��0�A3����sQ�E����0���N.���|�w�ʩ�w�Z��׸��>��-�䆡��tG085~�`zxD>�h�맭3��ESL�8�J�0&I����?�J�л�ou����M&��};���#$`�Ã���Y �^�NC
G��K<ޕ�9��QS��iU3��5�߬��칗q� p������WfE�0�;"'���[S �Y+�*���B��T�;k�Vt:QmUͯ:��`�1q��O�\x�b�(��h�ž��sLx�@0�,Bg(��<����k^�ȅ~�����nUIP��޴��g�pv3���1g����\�����"lG�UoPDkG���!WԬ�@:g��WE���	Q�+���F,
���9�K�k?~f�R��;��VA���;0y�<� �w+������*)J0�AG�;fʸaG�¯4� ��SX�Q�� Wr����c��n<+>ҡ��I���(kԟM��7���n���9x�S��x4o .	6X$��?�ŧ�<�	���k���Ӏ�z�.���w:UK��T{`�5q:�fw���:�:|�Eh�)z�h$vO����q��,�sgD�?�6�|����|�|�H |Z��Q=�Eg�i��f�9���,�ǈ�lo��ª~�_񷁄(*�,���Q����!ǀ$�~RԨ�O�ǿ��V���{�����1M�j{�)���F[�:�:�X�����,�X�VOv��z}����л�!�;�����I����@�誉��yf������=.{�r�(��;����u����wG���|4�q&6q���3q>�c<â�8���n,&��I���HU�r�a������dl�H��Z��-�#d�!*�&-?c�:��i�ꙁs����T|�U4��̢Jg�ˌ�˫�s��2=E�o��<��D!�ؽ��#U�&?���K��R�\վ�.)%	�:k)��BWI��T���XOg�)�a:�u�7;y@��FJ�1Xx	p�P4��ф�R����7�7��<Ip�ҫ��"�%Pݶ�$j�N��&�ޫ���zW-��㶬���f�x�⋸�0=qzy���H�#�E�Nz'L�r��Շ���W�R2�{�j��`�����Sv�Z�o_�S)+�\ijb��̂w�ӡ��^�̅��y�GS�IazZ�W�Nc;�<u�Yf�̗jy��&���a
���Y\�{���u-�ysسxG�u�w޾�	<Ѳ�����nO�#,�F���xM�V����Lt�3?֪s�{ �"���Ee�Yf��_���/�D!�5L�P�[�Oת�M�M��[ �Pʐ� �唨��ɇ�a�� U1��; �I\ � ��}&��H�	lr�M{aù�3�:F�D��V	���%FB���b����Q��:���?�������������*�0���@���ƍ�O����e-��Z�ܟT�����h���E0���g�>X{c?�`���.��v������#���Y	�ǣ������5���g%uyeiMb���S�j����JǓ��y?2�OFF�������`g�̚?������D�k�t*�����O�b4D�Vpa�)u�����,�D����kN��4�F���`ۈ�3-a�ڠ��0�ϪL���ƦW��eBв`�������AwO|�Ljr���Λ��4m�x��{���s��)��aÇo�di<���^%���m�'�{�Y�#N�ig��}	��2��^y_���B�,`�����]��90��3�����m�ج�.�����ս���Y]K�˻u�)�{��P��H>gCMє��Vw'�1F�]�-�&�a�����ܼ������������������������������������k��?��)< �  