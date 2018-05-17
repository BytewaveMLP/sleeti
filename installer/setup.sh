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
‹ ‚èıZì[ùÚH²Ï¯æ¯è8Á$qÚø1ûˆ#æŠd©9:ˆZâ°×û·ouKâ°±33;“İy/òÇ€Zİßª®ª®îêj%¹WúÅÃ•çyú-ä³[ßÑõJH§òùt.“âS¯xç³éW(ûê\>ñT¡Wöí—~íù_ôJrd‚]ÓÑ>ÿÉúÏgŸÑ¿ÀgSéGúrişâ¿ëÿO¿Ş¼æ††F{{ƒÍ5¦ò¤ckê·W\Ó°ıê(Hµu4S]Ã!H%SxN3BŞCÓ’D-4TÓ\¢‘ã"Ö®€tƒx®1ô=Ã±‘·œb†£c× XF‡Ä×&€(el{…“ëRBÕæúCßöü#Æ¢è{Ç- Šµô\]83Õtf'(ÅéÔR]€R	!Íj÷	Ö)ƒ#âø®†	íU¹W€ÇÏ›8n>Ÿ'mg†M3©9§9I“1L¸V=ßÅœ ¤²BrâYæº!Áî»#Õ7=Öò‹IĞ*NÜÄ™'t'a$F†­'ßKÌ'ª—€&*%œQÂ¤"J$áú¶mØãm|ÛX0\bªÚç¹
\¤Ò¹l*AµãZ†C€1än­H 9õAK.&À"A†M…A•c© f`iª°¥.b4U]*1ÇİpAÅ K"C)´.mådU`”¤…jMMj‰õw‘[a¦Qd¦ ?[b¹^\œæ¹Ü]Hr[jSÉ\2Jdª¥³òJCéÉR¿×è´‹:³(­Š­Fóz}_‘äÆ¥Øk\JÅJT&KMIT¤b.É'sÔ:©-¶¤"Õà;`3¥½‰AĞÈ01š«YnŒèßp‰È’xØJX:%û©1SöÁ€æ %&>ÅÄØ3PÃ;2MìFˆ$ú0‡ÛkàõxªbİqÕX,Ö{õ"z°OŸ¸Üê-Fÿ€‹ÊÍ·5f”¬ó´¤¥‚*Gaqd¦‰è¿CMV8ÀX?<B÷±‚4yãÛª…Q‚Ü°¦Ğ¨Ìº	ªamâĞºe}ÏjĞÏ ’1BÑşÁ}GyØGE´¯øvGÙG¿ s*d;¶·j)Ê4¦7{Á£FR ĞÁ½,]>ÜÓº(ª<»9ÚgÄVX¡ÁğßCîÌÇ¬ˆ«Œl‘¼qˆ‰ÁgÜ Ãè'J¸ÏTÅ3Ä*ª;7ìz[Í¡[«®»7;¡-UsÈsèlàíìL8øö×èkÎ™W0Çí°Â‘{ «óÙæ¿ù¢#ëÔŞ÷ï3ğÈjs.{8X?Ü°=p&ª‰ª¦Tä°§qAİ„‹M¬Ô»xŠŠÃÃäOğ8ùL(Ø‚®qò'Ö­8:  (õóúq:q¶–ÿãè>Í¦kqÁÕƒ…9À>GÏ‘ùµĞ[ÈÏâjØµõß«½À/Ñ8%-P1¢„~<1µØ¶=‡üVvi›Çìãz?1LyM.Öv`ÚÛĞØ2C¥ö„ê`P•Àfğ	2<Zæa 81u®¼2]gls<bÈ¿ã Í&Çk'·QÍ·?ÛÎÜfã&´ëĞV£ŞâÁœuã¹(¡£¸š¸ĞÇ_îâèĞs¿¡°ÿş`æÍÍÁÈ?-|2CŸ=Ÿ°Â2ÉpÕ›'RÛåÇ6s³Ù8‘0tø °¦ó iróØ†BÚn¼şõU„ÕRà„æè8ğ€/bÚ½,ø›çz©}7÷X*Sg(äpáö«Á6˜<2ÎáoÓ_‡.sÍ‰Ñ.ëÙ˜öş7\AİS;Ù`å‘«~ü$rÌóR‚Ü¬–³ÑÂı²Óf6¹Ú_5ÚíExaï”¾"=í[„«³N¥ƒ¡ó€UşâSC¹t•¾TéV³Rİµ µûŸ`ş£ø?]<îR’ÀJşÄq7+,6“ÿ
¡$*~Ba“Ù{Êß‚b¾-V‹ĞPƒ÷óñï|âì—7oÆñ›'B´ *sÕÏød„÷²0ŸP]÷şÓ!Ç­n?=/Ë0Â*Ç5
³ÛÀËe{¤>³LÛ±ìÂôè‰«İ-!}‚d-äÖºùúxñY³ı¯YÏô›-.Ü õ’:E$I&O¸xÉ@‚ğûY¬U½èîî†í$<„‡x¡JêçµCFEXÕŞİEméÒféıÓ……Î"ô”ÿÖ\ùÜüéÀjãõf°“ı_lÿÏÂXĞA’ão¿ÿÇ™Üãı¿T.'|ßÿû³ä’Tk´Q·ÖEJ£Ö{}Y
,ü2pzT³ınÍ„XÌx×ĞJ¢XÇb­ôî­Êan·R©"ËcµÑº¸´ßõ.w;:¾íæ²—wisvêIšÕYºåÓÓØ±Û¿µî*},7¯‡ù\~aj³/—ÆEÎš—&F«³pªuã«·Wê]ı¬Ôo×ÏîN9ó´u•>«ñnLÑËªees§§e{,`5õareLZs[µt_³çíªì×ç—šTò´Kç6­cÒ5KÃyƒ[4>¼ñâÕlÁ§ÍQOººÌ	ù*}é
Ş°É[eÈ±§*©+«’îó]mvÛ¬uN…ü|,¤¥!—sŞ+±K«&ÜrËšar_fâí¥–•Õ¼ìq†Üí/ÍÔRXæ>·[­¡œ:æ'x:ªg{3aäNM£t)Çú8y‰¥Û†=<¾Êœöå¥Ekœ×[ôMïb!á/sË8KÏ‡·Ji*êÙ¬àë‚÷%ë¼Í«§^ìNoÌş1±¼T¹å¥ÄSë‚oŸ*‹T~¬I¦å§ÜÑÙ‡¦~%7sÆed.¿Ô½ºcßM•Y¹÷î:–ÇWé_×¼Ô­Î9ù‹V=›MF_ÒbÉtãÂ®XYÜâÔzwªÒê]—wéÏæğ¬6i÷†±Ãõ3ÚİBçk³iıCı¬"÷Gª¤_¦Æ½~×ÄS¯kÜ)¦}«e²½†1î½·¼Y&å
•IÃUc§™Ï¡§º©r½q–¿˜Ûn‰ä<áÌËY®¥µwÍò¨^?ãJ†XéÕJu«G¼îÔ9§ÙÎèC+¶ø0o‚Ï§+Í·‡½ÆWØhŒøŒÑ¸[ôÏnÉÜj•R|µÜvªjÆëUªÄÏòÇ‹Yóí["Æšµåì¬Û¼32Üİ¨[[hB©+ÚÚ—ã3©]Ù5(şå¬%ùbÖ|ÂÉ@¥Së·òÿé,Ÿ{äÿ³i!ûİÿ£üO¸óÌa{††*,Åè¾¹3]ºÆxâ¡Cí¥x>Å2,¹ÔqUÍd)ÎqaõC:¦¡Âz/‰DÿY;–s Ù=ÉÒlƒ|ê:cWµèöÌÈÅ4%3òhDw–Ï2;FÙ"L7rB2l'}	0PäÛ°\fûğ4e P­İG5lcW5Q×š††š††mÂv~¦´„LØ&?ÀĞUÊr€ªà2³?GÑR?a‡@»{²fXò+–¯l[øÓ}¨!¦)„‘o ÔEï½z§ßCbû½eYl÷®ÏYŠÁ§x†$Ãšš4ƒ <ºªí-#º/Éå:´Kf£wM3eÕF¯-)
ªvd$¢®(÷å~S”Q·/w;Š”DHÁ8ÌÕ½ «“6V´ªa’ Ï× œ™:š¨3jÒ0D+:R‘–òu †jÂü%QÖ"<§›J/œ 9KzÎSíĞ$ÆJ?'¨akÉ” ’j6Aè
4¯#€®šã ’C<Zµ%">%|BHóB}Eü3ò?1İ|ñ±»d[/{ÁN5ó«US“h3fÿ µ^Q<¾ŠœööÖÕ‹‡û‰)Ô9b1Ëzƒpÿ@Ø‡è•ÕD	¹„Wû÷ë¦ÿ÷—‡ı­=8º)[o¡ƒ¿Ñ ˆ`o@[¦*!sÇÕW;{ÏĞ!Ï'é¨Oh_‘z`QŠò¾#WPqõó0~ ÄÎƒ3hY2ëäÎAPˆøâŸ‹l:ª>˜BèšãÛ¦%¬·Ïl³í÷§ÔÀŒd*‚nØ	TKBİHrQ¿Ã.=ĞMXË™áj;öÒr|2 ¥ºd%u+RSêI¨*wZĞ“´&z_—d‰f”İb<~¾¿ftKÄ!úåa&ìßG¶Œƒ{¨ :Ø2jwzÖè‡q¦¨	ÅOP\Hå“<ü	ô¦P`êx™/•@ˆ*xz¼‹3¹ÓE°†¾"¡FIWU+,šßÄü7¦[a‡nåÔ–F^ê¾>;_ã”V#‹n>}ü†8ÿëˆ‡…| Š–·CÕf_©£®Ü¸l4¥š¤<'İ¿òúúAò§ûÊúoÇù/AÈßÏ}Kı{scÌı×è?%d¾Ÿÿûöú·Ç†½(;ö(Io¿•ş3é'ñ_&ŸÏ}ÿ¾ÅuÿM5Ğ¹1NöšÊ@j‹¥¦„~xˆ‡Û¢õ!D9“d2iDˆÉ©¥Îƒ•«6`)Ôûû©Òi‰öC˜§- °ŠíÅöàç@Ã.;ƒ !c0c¯šQÊ’ÜĞãQçOê>ãå£úÒõºzĞ€`B¸¦jF‚L ŠĞŠÒ,Öùv%Ï°0¹P 4ñÍ1	3!™Z!ë“©Jã¾uGëk‰­ÇhÆ”£Bq©\©K	Y¢¤¤²¹D­ÜJ(u1}š)l=R§Ñ3¨VxÒî¹6/ÔßY¹ğ´m¿uæÄ5™Š¤$Ê¥r:(®ÔŸ>•ÂÓ¾os¶ÍÑ'›}Ú$QoÔê…×j»ßl^ãàKºêvä^áuYlIÍfC,¼†&…×­J¶ğº«\^ËåLü<Ò>A‡vi22`ÃÖñ±Ïät2¥&Ãâ±û{úõğÀ±­ÕÕLzÜf`©‹ÁĞÑ—bÜa$ğ{¸5òê°ÆŞz{ª®&X¥;Šçš—èA¬K¦ë%ºUgx°<à„:ÆÅ´MçÀ“B,mk¦¯cÅê¥69ß?g¤°A.…ÂSvğ£Áêç.l@ Ë>8Ä­:x`êÉı ¬-„¢O´Î.œb†Ï@õ½‘J<mlÈÔ4hÜéM†=rĞß“Ç¬ñÑ!—<>: uCîQÔ†5²‰Œ>¥,7º½=³CÓÉè@w4ß¢¢¦:8X‘dç1™gz#hÿ•Ot¾	FZ…a…3ª…@Hoí%R5İÖHN¼ğ†‰é„ª_œR'(êîic0P3	vØÄµSÓÙ?÷	`™ü5z„“ÑgÁ~è¿÷Bç\§”ÁM§ıÔk3£ÂºmB=)pÜ;\ÌN9@Éç@†Úê[Æï#ø»FÑáøİ¾ÿ_Ø÷¦S{õıúòÿÿ™ø?ŸR©'ñ|}_ÿÿçò?l‡\	ì”æG\–í şğ–¾ÈnÓo¥`ZÎašj¸õ(áæÿ	š%Q
üjŒeZİ&Ûƒ§†6)«4 PorÂR4Cä=úöx
¤zÑË2V@9é¸c 9 Æ%Wı-<z?ƒÒ£¹:C1:*ÒíP=ÜşgïÉXàOXª&L|Ì÷3^XÕ¤XØ4w¼âØæ2x$8	EÛF‡•¥Õ¡ôà%)”Of“0XŞˆ8Ğjˆ7 ‚w¡Iòô^i¬JrPr•V‚¹8•?	ß©ğ”¾y%ÒW¤²*Óİ?…Çg¹/œmP•=‹è|F‚œÏFWNî³L‹	[ÒC\È±C“ ŞÊ’q@’Ñ!îF”:"Íd³5 xu0±0oıúIi³ƒÜ–ºDØu—æ¸tÕ‚Õ.5{¸‡™MÍ@{ˆÙt?x¡åp‰½#¦ş°
ÁJàK#¹ôĞ?†	gLß cÑ#“f1=²èÂjõ£NäÌÅ²W²\<uWZzxİj5»ai!ÜÉä«tŞ·›±2èËÍâ~„²°£-}{Í˜aÎ‚Ù»I€Jïöc±èØ›„c±F[é‰Íæ ]k´¯ŠÂê¾%Ê±RÚ(éÖ»wÍÊP+wÚÕF­/K+”ŠÔ“Ê½Õm,ÊäN‡FäAÖ§¯ŸôlëIL¹hte ô»4â”ëÒ»¾Täc1`e2"U¥F[”¯‹qXn@«RS,_ãŸøtú#æ­xL–*ë
j²$µ×E)(êÈb»&­ËÒPVjö7J2PB3 Í²,”•¯Å¬”4!jì€Èõº<oÑ¾Ê›ÅBÀ^P}Í¤0¹BùW{O»ÜÆ‘\şO1†‘ ĞqñA}ù(C
M‚Ï’ÈĞ9
Åà–À’Ü#°€v‘ğÙ©¼AŞ$ÿR•Jå_^àòFé™Ù™ı ”å‹¯È*[Øİ™îj›Q}fçá÷ñ»‡º°p›æ÷ÚmF›¿È·ùï_ôŒ¢ˆ÷›]Õx(¿a”lß»!«v:äKá|åÊßgì‚šwéÍú,ëH­•5ãSÃªÊÏşdê…´?'«ÈÒro‘^Á#ÿèTj2$ôëZ¢‘ºè,WÌ˜ÎrİIr+•Ë—)´líá±:l§Ñ‘MJ!/åÔ@!õÅ
ş	Õ“M1Q†E¢lm4Én”9†×Ş|Ì¨¿˜³ës¸üÊú€&<gƒ@±¡((’€’Njœ.Ü±?ZXÃ¯°ı(80Ğl	×ÿù‡¤!
ŠìÂ:ƒ"~ü1õ~\dÃ¯î4Pªú²’ÔSešæ°‚bóEŸD¡‘ är&½y`%†1FÔ×€=ãêêÍ:Ô)†fºf[ºDçD,àÛ$İê`Jª¯åJMÂU¯Ê|î¢zº=Ÿäí³*şMnèw½lpv\yÃ1LîÖ{ËrXKúU[bnÏ: ¦§Z<'ëö'_5µÖÂØœëãÌSHâPÓ Ô4[îœÔÕåp¹Ìzpñ ær¨Xb=˜|8q9T.³\ä¥~ƒ8	Aİµà3IhÔDËÌ)jÕË òJÙòGÜÇğêÉğ‡Ö³gj•˜Á«ÁÕ’7W÷Ã". –I0ë³Ş]>a4)JüÊ£9ˆæ ¿_ƒ¦ş°Í(Nğ+0õèfè¡x„R”†,ô›O/CwˆÅFšaOå|ìÎç¨/ë ?\òõAN¿ ÁáÄcÓÓ…éä†1†ƒyâB¤"ÁH’]OŒ›ÄrâHÚ²ƒ`!@|7SõG£•în[âëEî@¯Ì<Öàˆ[/ğİÑ’ÌB ébI7œ]ÌGBÙOí–Á`ın>Z|^€ÑğŸ´e¿˜Ærı\c°M“|­±F…
‰–=9Ã®P2FıiãÁº<1¿r[©2È¶¶À£î€v8àÒ™ğ„yÌBãÔQ~üíÑ<”1²¨ùŞŞRñ‹†äº]„ƒ‚Jïú¹Æ>ÌüEâÎœëi–€…Âè„\"èÍég{½TŞìbHÚ0Şşn+®C.dØÜ´«ÆÕO×bÃ5¯†w;İd2Ó5Üèº¿P
é6h¼¨R¥-Ê´Zº`•†SQ®-š@°²pƒè†l3£ÀFf iQù—Ønm·ŠçÄâÇw"=)ä‹˜oÒeÚfîÀ
ìmä—àMJæÕÁ àNµÂåªD'>K+¨ÎÕ’V´U'Í®¥ÖµM±r*œ[QÖyeš9ª`^;T!»òNqÍ¢µf¥‰Â÷áôúR8#Ö~Ícıê˜´Óåÿùw¿ñ $İ–d´øBoz°ú´%‡L¦ú¡W±êW®¼0F·ïÒÆ˜]ƒßmåÖáÁŠÙth×Ros«İxçì‹µêõ~’Ã^dp“1ÇE™ü‚²2
=ôKÏ:,ùR¤Y>ÄVràtD·!¥`ÚĞÀÂÉÀó†
0¸Eº50çMÈU¨¯ÄS‚)¨12EQ}%«|2²PtÍØh‰:EeÛ|F¯r™lì†¾;<Ï®²¤÷Ÿ4­e£³÷Š).ßegÓ(EoRn½8ù‡WŸ<Ü¥8kJb¬ƒ'ÖØûæÓ1•pîÆ—Ó«©ÍbğbM‹+>]^3wÃâiu%äFë®°­ĞÛw†Ş. }ëÎĞ·VC¿3a
ĞåÎd)@•;%&&wÃºòaî!w«<#’Ë+Yp1'GÁYMîƒv8“©†¥å^aÿ`oS<Å ‹ºxŞ-ñşÛ¬ëÏªé’6/_h“²¥´bK@`Ô,Á‹R½Äx<«’µf˜Fæó«²U–³d®ÔÊ$gKîZlˆ²ÖdJ0„¤À=c< IÛÆCİ\lëˆò‰7£İ€éì¾: ÓlÍ7£ª˜Û©­R†5Û	¹rZ¤MLİi/+eXj bÁ¹Kœƒ]°©šæ(“iñÎ“ĞØÀİ¤ó5°á$Jt7Hèí}Ü•Uî²Ú¨Ü9ªÙÆô
ş3ôª$ßOgÇî„CZgH“TÃZJ×˜ÏôF}+“ÜËêà½<¹€‡R‰94ìa§ùxj»A­¯R¼ÜéÌ¹ô4„³øğAŸàv¦!:Æg¾9hªÂäÃò3´LñèleèçCr²pâ_b"\ñ· G
@¹öaL#„ã¼Ò¡D
ş¢?ö#ÔÀ?*8 #0İ\œc0‘)‚À°ù³I¸CuAÅˆT˜(WV3íZ¾‹ÓLù˜	h9g}$ñİEE¡*Ç‹½w4\9 Æà®Ï#	0Êze?f©íá•É9‹ùªçƒ@c8˜„Ğc­Çáts÷^a"Á1¹¥‹2EiE³ˆµ5ş)'¬åÕ3<±œ Òô)Ñ¤ìX>D`‚Á|&œsg+.I;•ì—M9©
Ãk'à­Ë€vËjQÃ®ÏÄ×_;¢{¸
&ò5ˆF¡¤7>•Né÷Y	Ù·ÃÔ<Æ÷h-ÁÈwdÔ^ÆdÁ3Àˆ^³‚ø6+²srÑ&t½Yºœ^Ÿ®;í’G±dCøh•
ó6"^ÎaÎeŒMøV`CïÊ“eŠ?Ì½¶ îˆÙVª—Ü•“¨Sì€pN€¡ƒ‰C1rá˜:º^ÆIÆÖÔm+Zv!c±wk)°(dMeá­”ä±LşH©<ñ1‚ßø0^]#§èço†aüÜöW-Ñºİo?yüdëÑîÓG»{í½¯–Kv…ÚÚ²cL}ÔAÓıK(+·PŸ¤ E6Û­ÆÖÏ òq¤‰¯HRHæÛ—BÂ)*ÙqòJLHz½v¾=úÖù®ûÎ‘ K+ÛN‰÷x:ËZÃÆ˜İL@£vCô¶0ÚÄ$¸Ò*w´0†n,ğéCã3-[õ;¯	í»
uEfëòI
vEê„TOÎf„üòJ€»	|›µYäolÜe`†ÁEb‘HÍ„¼I¯/­ÌªrÿO…öf#¾¬‚]©ÃÖñ^vIô¸L±L‘©øú5ŸbL‰;ÖU¬`»bK—‚»jñŠİ«*¼
dË[„Ø`âdäŠòf‹€Ôˆ $²ò/³¢=ÚÔİiíìto=yºódwùŠ†Æzæj–;²r™›NİÆÈƒ«©;lŞ¬9	†¡÷Ç&©97Ã"²V+#.2+–iµ¥4AMtl·@ƒŸq…DZÙê\ÀšÅ?Õ›áD½qşè“¿§Ã‰ú9>GAMÈçüi!_ëêõ—5:EV5Gé¡S@Î`äc«3…–C"–6ü-{`áéL½Á1Æƒp1%©s;Õ—‹ç,'ÄN?öÆ>…1£uÔxÁBzğq¶©8à‡O©»Õj?ıÄú_İİÄ´ÖÅ%R‘¬ÑĞş2ÂNÒZYÎÿ4¦ÜVß©ÑN®)Û€úå%Ëa3t@J ™^:õ1šá¨ÁhMÃ	GcI1?ozSoDÿÓ¨ò™'O1j\M¼‹óèuAl3I_JR„¬íúì9T–b¥K•ñ`|ÓIv4X‚‡SdH+H‡Ğ¦C³’İ—²pF3ñ4•¶‹æÎUà€x—Ã¾:ùÜ¡ğ] ¼®Æ¯KÊ™Hdr©gâf%•#-j~ÎîğF.yà€WSh›•Mİt6ø8Şå¸£¹¿”ú™RÉ%ù¬C^åÌ\gJÿ†$ùSXQ@ğÂòS×KãÆÆ—¢¨k§ğ)šƒx§é/{‡}í ¦ß8Us¹Zd-Öâ`/Ø,·Ìúx–+R”¡ÄˆœÔÍÜk)¢¯Z8ëf¦Ck°
Œ•=J¬ "¤Î4ÊÕ¿å1E¬`Ş²‘«{'	’)rÉ½,[_¥Ÿ'6„,ØÇ ttÙ½G‚>ZºrX¡ûGİİ ¤³0ƒ›± ·uä	K¢N¶Ã; Y/Ü~\Û›šè­#˜ñ³ù”6ô¹N€¦0ÀµH;»½·;¯¬äîŸ–iÿèzEÉ&jUÎ’ƒùÓ­cºÇz©”±qåÒ<ôn§´Ów)&®<ú<˜ÍË3¾«=!Eé1 <¦åJ²gåì5Ú¼­v·ÅAğÑù™8UÕ†Ú8ƒÔòî{‹¤Õ({~¢ E`!Àè²Ó>›Ö™$·(íIœ‡Vznh^Ê› œÿ3n§÷ğÆî´Sãô=b6Šú,ìè'fÂ¢#ôJ/?È¬TüpÍT:o¦³*hÁhÉw£kQş’G$MdSt"ßÇãì2\ğ…¯âM	D¼¨K ıE pèh<zük#»	ÛŞ«“ååä4À¶¤0U`÷&Óa0í‚cÍ]„)ÌÜa:@üEÙ\8³ÚÊèö
?¦%ç20@ë®'ğTİ_ €±Z
4¡›äÓWæ?p9ƒ¶ÙÄEFÚ+o*j7 Õ÷…Ò|–R†İĞ‰W]@‹Ğû‘
Œ‡¥QËfva!R{ê„?À(qT.‡ØÆqü>YøZ6h}z3y‰eVLB¾n´ßBÈÇS1Á±â_õ)É(—!'·Yûº+NX§;ävğ&y!j» HÊ„™<eHÙuqÙãCº*¶ãÌ&Qİøœ?Š¡w‰)’B
[&@âxyïîï¶tu-ÒFÁ»B n<Ô˜m¡¡pçãir«³}4O¢4—Ù¿‰’Å±vËÎX`Ôz¹¾L¹é£:ìÌƒÈ½ğFGæ:wŸz¶’œ¯3îãŸÃ`ÓK¢YFH®²¹€fòÙKÚõ`CÙÎ”|«jJ`·hBL«´|'œ k€2vçìv¨19:Ş@‡ê„Ój£éb&%ÌòíNO[\”3Ó…Ş"XL<*ƒUò¨·Ê+ª¹#ÓE]²{¢•¡Ca˜âŞÅ)=²£;ŸMìzWã	ˆßÜŠÜ"«6ë¯ç“ÉL
wú‹“.ÁÉ3q†{w'ŞèÂA­Ãcn’¢¸Áò”2¸ÌÜk¼è LVL4iqÚøzè‡Â™ò¶)ML•™©óßdêX$Ã±€Îæ0r·µ~û^L†Ğ+t†aBË²¶©éÑ)&Op¢+wë1ÔÄ´> ¶¼éÄDsn·~+œ¡»ˆÄÓ‡-á,‡‰£G@lE® 2`×ÓFµ4ù#Ó!lMéK+‡©ğ é-–¤“ejY5Ãû…xÃyˆ©ßBJÿ†WX°A1g~Áó	t‘¸(ã-Á%°,`¢¡Pò#Â¤fµI–a¢\ÜÎñqÁW8ğw—½  ø—]¡fo$“I6`.®½[ô¶\z‰ËÌû¸ıô«æĞ¿¸ğ=¯ş3Ó!¬0˜1rhQw`±ÌGDeÇ½@Y¿õÈ!dK&ëªş8ÃÈ•¿³ƒ¯†F] s'5Æüâ	U2WCÅ”›RA2ùVÑºNh|k:™táb¾ÃÌŸõ8\oŒ±†áÂÊ>¾ÅõÕG{„Ó
²òQY ªcÿê_šÅ˜Ú2UÛ¤æöi¦®†ÈÅŸ?©j¢P79—Z¼”eÌöfÈ\å\S*¶†9L…ÓV)B‘×ƒÌd8®ŠlQ$Êrø±1lfÉÅñGÁ©“BF%ãk)ó@“¶Ä¤eö¥8š„œĞêTR ô•ŒÜ{’c.q$D¹¢èfˆt–Y/–Ü¹¤OÄU92|J_jb‚V‚ $)‰¦ºÜC:…°õN¥F·ÄóO®óCËùmoFzÎeİÅ]ˆ­Gğp‹	1ëK:È¶XÑ£8$lQö'¾YdØõô€ml°&ğ¨ÕZVSM<û®’¸õt€í±Ùo
¿ÙFùD¨³d»rÌÈv5á£?™G -M‹=ÓŞ³h
û7#7¸¦)À¼ª—ÕÀgº*ñ:œcú:™I“çEæU%’àöüHºæùâF.Ñ¸–d	49ÆL8Ö5"K!áUäV·m¾mCBË»Æ ˜“‡¬Ï+›
«]sf'Î[èËòÁš¯HkÚcPcÛqõÏ]ö¨vÎ¥…7™º#ÿOµÚi‰áù¨RÌwĞd6lS…6)C_3„—ş(|©F±ÄÌ˜4äL†ÅôìËL¹¯[ê’“>Ÿå“—‡ßkRŸˆWßuEÓ¤|ßMöHà}E«ìB4$€È8-­¯N‚8—3Cnl¨%gqU‚ªÆ½WŞ#>2İYV¸?á£‡à{T#Ì À`V{c£PÏ‡ádê³¥@¯7H`m`Îà$‡¡‰BwD)ÄR¥RˆÁs ›yñ ÁºàH­ÔÑ3³òë4vîĞİG™€­ü‚K›äD;ÈÚåŞî¾êîöÄîáÛ7½Úƒ:_<ôşš»ßÿ¡ñş$bßÿAŞCTõ‡U\Ê÷åâŒïÇ~°¤§‰ş,cûä
š>¬HŠÛé´…t¥4É3µ±¤e¹8¹ö§S3ò3ó0’ı–Êj'—$m _õ¢Xu–£ª.=á„ƒqƒ×§j% Ñ+óX%$qYœanè	N•­iA5¨u%U_ÂM”ß.'—€MÎ‹Š_d£‹‹ë<›ºÁ,’¾GŞ¶ÀD3|ƒô¶&¦6‹3bø&Go>OÀéC÷­Wô˜t_ï¼êT˜Î‰µÉ@A•&0µãÈvXÙ[1¼x#O"?qøgŸjÊX’]ĞÆã­rfşŒ8ú9Ÿ¢áë]uP‹fu©‚ÅJ>£…éH‹Ç%‰¹	ĞÂ¶½õòÚ_âmz¹‘.ŸtX?ªÈ0~Rácüd…ŠÉWt3 Q]f]ú3ew>`¿,´mı@¶5jËD){_–Çª¡T€˜}8-n†" ™7İ”8¤Í·áä&@Á3wi-@}¥ûF/]?xQ^ºâ›;Â6F Ï¤¿e¹NKQºFµªtñŸÁ,¦9€dî"0™½Œı”Äb¦£¦8Ã|Šô¨}©æK¾eÁ|ƒé™QiškĞ×cä²É+?¥.fcGÏnèOÚm ‡Q§-œÛ’ ¥gK¶©ı	`2¨oH‡¡Ì;tƒïÉËÔf¯AûPdf‘Ğ8UjÀáN“¤±õ™AR55ö¸Q—oó	Y=ßÑóÍ//Ñös&6Y?R¬±®poÀ2y·EûIì¶ØØ(è´À•ñnN‹DM\6sc+S5‹µN0vß˜ÖÒOvBKmY{Õ‰t½ôÇÇœñES:éH*§¹î@%>‹DŞ6ü`™–’ş–ÙDÉE••[VI¢DŠ/Mw¨íU*"ûê‚9šXí«âÌT­²³rUI#òFñpÂ¹ÊŸüfÓiŠŸX¬’Îµaåîr<İ°Ó›a÷Am`¯ØP3ÔÏ½ˆÁ)ó•[Ky)³Tş	€;­¢ó/âŸOaœı¦b÷{#y¹%¹Ós-4E-˜Ïi“zˆ9$Ú[›âëxØ–Ùà€ûO«üÒ‰í¯t^ã^2ë°6«Û>ëX%Nj¾‰	²÷³w|ğûîq‡òÀóîËã“n¯3Ÿ]|5>D¯_½ÚÁÔâêeøxÌ ?ğñûËÃ“^Gß¸K@åïhãßjE;~Oşl¡P:îîîõ )‡ïıÚëĞ‚ñşä ×ı®û®S.›/»»Çİ|½¶Î~ùLE=Ã=¿aîd$ÈkkîSÌcñ=JRog–D†üR°gñÇµ‡	Ûæ™ê
neúñv3-“È‘i:ÿUOíÉJÿ¯¶f':1£ Ö†kÎåæktå_Ä»÷:‚ğ¹úÑÁ½¯©‰ƒqz®ô6r/½m_§#N·NÎTÈPfŸè‹¼¨‚_ø|7J*Wº•t²„(m
ÆIdüíùÑt„6/ThCNscè°şG¬¯9&ÿç³é<Î‹‰má˜¯eÒ0ˆøŒÍk¢æÇuËV	‰É“Õ˜–U…Ç¶¨ÈÄ2ÂßË[\²/n·†¾8jL|¾—î¥Îƒë ì,N÷XEySßÆÇs(·Ë«Ú•ÇşH¶ıD×ÉË®ëÀÙx‚Ñ]¥8¥$fJ·óæKÑ½õsÓslß{³“l0©íª$š¨Ë çÌâó[Ï4j(·ç•\K“¯£••Â…é`^exŞ0’Şèp`êQÑ¬¿²&±érÔÌ0OÚíÿ¬iŞ5)çA ZWøv'‘VQN’$[´Ø¼Æ ™ş{uì¢m‹ÌÄh2¹X7ËCÏåd|an~\½rhdÁíút3ğÌ|c|QªeË@>;	›Ì$º1t-†(ì! .ŸÇ±4šW¬Œ}úÎ	}İ¦xƒ[Ã›ØŒÎßE#‘Ì™YZÃú¥x	ÂV:)³ç*èü0¥E%7˜2b)ÄÀ¾ÑPşUJú›‰Q7#ãDA'<9#g/L"Y1¯íß|‰f„ÜÅÍÆDN+3W§ÉkZI³,DCU%ÓJ6˜Ç~mÇ%ò.MâÊTÒ¥œqv'B$Ö.ÙC¬>ßu„]£Bƒ›…ÚÏ8ÌêÌtî@«Û¡r†ÔèMjDÓ°W©IüOR•™æìË—¿ğÔ51¨†`æ7Ö¹ºhœè,;ëÚ]GUèCLyé
Y}µâ—t3\³§øh+²¤F;ã9°*çc#Ùº‘ê\ÁÔığ$ˆ]ç#Y wÏôËÃü„x™ÉË’œ¡4XÀd´¥yøäu9re“'3IÁÙËH”Giì•ÃÊ:vgª3’S¯<ÜeY"ßx°Æ±Ô9{ÌmuFßÌã–Óh~üON³q:‡Üöâì¯Y-âØnMIƒ‰SwFcŠYŠ·¨=¨ùí¥ÒdÁ.°ñ‚.ÔN1VMñL8”'N,:•«ÏŞ¬ZÆ+må]O~héofâìZèe„¾çF*;>hA«Íœ:ˆaÕI'ŞJÍ3[P1³ËÜ­Ü@(FåÔ°Ö}£òœ!1‘6’O.™˜œè±’À<ƒ¦‰3X™¼³+JÆâ˜€LÂ’zæ–¢Lbcùyò‡@e·½ïãYkËS·Öm–'ƒšÅşKİ?ö¢LŸ³Ù%;2T9YT5¢¨‘42qr<™˜F‘×ü"ƒp³(ŸlEÌşÑëâíYg¡³ÛM16°å	>0vˆöo§jû:Ä9‡¾gôíÄ®	<\š¹¡ÔÀOˆESƒ#Tãçˆ_¨‹Mwı‚FrÃÆø†í¦…½à“2xNğ°%÷÷ãwQsÇˆ™ÍT-:èhµ°‡‰®Ê¶³‚=÷wjÿºîÿ™ú/sÿwkkëñãäıß­'­ûû¿ïïÿş5ßÿ¸ú{Õ½İK®¿Û­Şí‡ÍÖCºÔ;ë*ì"×+ÛWßı6ªu.‰²”Åß{¡Á÷É©\ß}kå(¸œ^ò†±H,Ê¥R"¥½LhŸ—2àğ»F9]@ñ]0¹|r›™Ò¸òÌpÈË‹P†–³§=òñşd$/€…éd¨jÉ/Y{´;ğ Vá¬ÍÀÁ†P¿>ÓÊŠKJ]Hê/ùšF’¦ª¢®N­1 Š­¢Ã)r¹0ÇS"y‰“¾è?§Å‘¹_®?Ãú¯óoş2ëÿVëQ»•XÿÛOİ¯ÿ¿Èß×/Pµ/5<(‰?Ë²`~uÀÜyáš¥æ3èİø—ï_aägø~e ­Úü—Iü¿×éıpŒÉÍÌåŒWÏúÉÈ¿	ˆ¼·¾ÙŸŞ†~òÕKÛ’¯=:qûÍd¸H™rø*âšS-ı:šN`L\w£ğâı·s7Ê·£Ñwæ½Wş÷»î4š¼÷¯e.4è¾|•Àş®UÜğr€@m^Ò1Í¼W«â®ªHQû>¨ÖŸázNnXS&°T¿¿wpÜï‹†¨6eˆ$:xh}ÁtFĞ@}ù¢#°‘§í3|5àæá-Ş–ÈÔêÆ'ç¹;Äpû€Ó—ÔN	­ê0ôA3¨âïÎsQ¥E®ºÉß0 §ÊN.ü¦ƒ|ÔwåÊ©òwõZÒË×¸­>©è-şä†¡»ètG085~ˆ`zxD>¼h×ë§­3ñâESLƒ8®Jœ0&IšÈ¹ª?±J…Ğ»ğou‡áõ™M&€¾};šœ»#$`üÃƒ»£ÉY Ò^ÌNC
G¬ÕK<Ş•Ì9‰©QSïâiU3‡5¬ß¬Öëì¹—q‚ pŠııÃçäWfEóœ0ò;"'¡ç‚Ş[S €Y+ò*’’ B øTº;küVt:QmUÍ¯:§Ğ`†1qŒÈO¥\xíbğ(ÖËh€Å¾÷§sLxÆ@0‹,Bg(ª›<Îó¯İk^•È…~½ü§nUIP¢ÿŞ´‡ãgÌpv3¬ÅÑ1gô«Îó\³£—à—"lG˜UoPDkGœÉ!WÔ¬ª@:g÷ğ¤WEš‚ö	QË+¡˜†F,
Šµ–9‚KÚk?~fÛR¸Ï;âáVA¸ÛÜ;0yè<‹ ”w+Ç¤³ƒ¼¢*)J0òAGü;fÊ¸aGíÂ¯4ƒ äïSXÓQ²ß Wr¥³ÌécÔån<+>Ò¡ÏĞIü§ï(kÔŸMñ»“Ã7ı£ãn¯÷ş9xÓS‚§x4o .	6X$½?†Å§î<‡	¿kÇÀê™Ó€ëzä.†ñáw:UK¡âT{`Æ5q:¥fwÕËÚ:Ô:|¥Eh³)zŞh$vO÷Íèqƒ¶,sgD–?Š6‘|¹±Ìé¢|º|ÜH |Z€ºQ=£Egê‚i¹‹få9à¥é‹,©ÇˆŠloÓøÂª~â_ñ·„(*,‘ö”Qªô±¦!Ç€$ı~RÔ¨ôOºÇ¿ïŸV»¯{İşÎŞŞ1MÊj{ë)¨²­F[:Ì:ÂXü¶·ÇÀ,µXÄVOvz}Œæ…ÄÜĞ»¥!;ıÃÛîI¯ÿöø@½èª‰yfÁ×İŞËÃ=.{„rÃ(ó²×;êï¾éußôú½wG²åñ|4óq&6q´èï3q>™c<Ã¢ã8œˆËn,&ˆIÀ¥´HU”rÈaÜŞæó½ûádlªH´ŒZê§ó-€#dâ!*Â&-?c¥:­ÒiÆê™s¬Ÿ™åT|¶U4Ö×Ì¢JgíËŒªË«œs‹ƒ2=EÕoÉĞ<§ÆD!£Ø½¦õ#U&?‘¥KşğRÍ\Õ¾È.)%	¸:k)–’BWI¶ÕT°ÅØXOg‹)Øa:ä‹uá7;y@œFJº1Xx	pîP4¸‚Ñ„úR™ßŞæ7º7°­<IpäÒ«š©"Ê%Pİ¶–$j•N×â&¼Ş«é€zW-×Íã¶¬Ãû‰f¤xùâ‹¸‘0=qzyïøğH·#öE÷Nz'LÈrªÕ‡ï§W¯R2†{Õj™ª`®†®SvÀZ…o_øS)+Ş\ijbèˆÌ‚w¾Ó¡ØÂ^ßÌ……ªyæGSÍIazZWÏNc;…<uğYf¥Ì—jy±õ&­æÔa
—™Y\³{Üİéu-¾ysØ³xGìu÷wŞ¾ê	<Ñ²³Ûë‹“nOÈ#,å¼Fæ˜‡xMì°Vó¡­ÌñLt3?Öªsé{ …"‚É³EeÆYfÓÁ_À¬ô/€D!ó5LªP–[˜O×ªØMÇMĞÅ[ ÀPÊÚ ¿å”¨‘äÉ‡…a—  U1·Œ; ãI\ ó ¢„}&°ÖHÔ	lrŠM{aÃ¹‚3¥:FµD©ŸV	¦ªø%FB¯¦›bëñã¤Q°´:›†Õ?ÿçŸÿûÿíÿõÏÿñçÿ*‚0†ù³@õ¥óÆíO½¡ÏÄe-àá™ZõÜŸTóÀ¾›ˆh²‹ÉE0ÕÚÇgä‘>X{c?Â`ÏÏÊ.æ¡v–¢Å¦üÔ#ªÉıY	‡Ç£ıÙäÚû5Ïş…ï……g%uyeiMb˜ô·S°j£ÏËÉJÇ“ÈÀy?2ÿOFFÜşõ ½Ğ`g®Ìš?¼‹– ‡ÀDôk¶t*ª½ÈòıOÖb4DÃVpa€)u™ªİúœ,ƒDú«™ÇkNÊÕ4“F…½ã`Ûˆœ3-aæÚ èı0ìÏªLé÷÷Æ¦W¹¸eBĞ²`ˆƒ½î›ŞÁşAwO|óLjrıíñÎ›à4m›x¤{ÿîÁs¯»)ŞíaÃ‡oØdi<½ÃÜ^%¥”m÷'“{ÀYä#NÏig³}	’©2İÆ^y_´ó÷BÕ,`¬»Êæ]Üä90Ëõ3£²™À¨míØ¬®.ıÇÉúÊÕ½À‡‘Y]K´Ë»u¶)÷{ªıPùüH>gCMÑ”ñVw'ã1FÑ]¸-Í&“a£š‚ñ‘Ü¼ºÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿ»ÿûküû?¹ )< ğ  