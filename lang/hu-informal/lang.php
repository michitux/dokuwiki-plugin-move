<?php
/**
 * Hungarian (formal) language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Szíjártó Levente Pál <szijartoleventepal@gmail.com>
 */

$lang['menu']       = 'Oldalak és névterek áthelyezése';
$lang['inprogress'] = '(áthelyezés sorban)';
$lang['treelink']   = 'Ha ez az egyszerű űrlap nem elég, akkor összetettebb átrendezéseket az <a href="%s">ágas elrendezésű áthelyezéskezelőben</a> végezhetsz.';

// page errors
$lang['notexist']      = 'A(z) %s lap nem létezik';
$lang['norights']      = 'Nincs jogosultságod a(z) %s oldal szerkesztéséhez.';
$lang['filelocked']    = 'A(z) %s oldal jelenleg zárolt. Próbáld újra később.';
$lang['notchanged']    = 'A(z) %s oldalnak nincs meghatározva a célpontja, ezért a helye változatlan maradt.';
$lang['exists']        = 'A(z) %s oldalt nem lehet áthelyezni a következő helyre: %s. Ilyen nevű oldal már létezik.';
$lang['notargetperms'] = 'Nincs jogosultságod a(z) %s oldal létrehozásához.';

// media errors
$lang['medianotexist']      = 'A(z) %s fájl nem létezik';
$lang['nomediarights']      = 'Nincs jogosultságod a(z) %s fájl törléséhez.';
$lang['medianotchanged']    = 'A(z) %s fájlnak nincs meghatározva a célpontja, ezért a helye változatlan maradt.';
$lang['mediaexists']        = 'A(z) %s fájlt nem lehet áthelyezni a következő helyre: %s. Ilyen nevű fájl már létezik.';
$lang['nomediatargetperms'] = 'Nincs jogosultságod a(z) %s fájl létrehozásához.';
$lang['extensionchange']    = 'A régi és az új fájl kiterjesztése nem egyezik.';

// system errors
$lang['indexerror']          = 'Hiba történt a keresési index frissítése közben: %s';
$lang['metamoveerror']       = 'A(z) %s oldal metafájljainak áthelyezése sikertelen.';
$lang['atticmoveerror']      = 'A(z) %s oldal ‘attic’ fájljainak áthelyezése sikertelen. Helyezd át őket kézileg.';
$lang['mediametamoveerror']  = 'A(z) %s fájl metafájljainak áthelyezése sikertelen.';
$lang['mediamoveerror']      = 'A(z) %s fájl áthelyezése sikertelen';
$lang['mediaatticmoveerror'] = 'A(z) %s fájl ‘attic’ fájljainak áthelyezése sikertelen. Helyezd át őket kézileg.';

// changelog summaries
$lang['renamed']     = 'Az oldal neve megváltozott: %s → %s';
$lang['moved']       = 'Oldal áthelyezve: %s → %s';
$lang['move_rename'] = 'Az oldal neve megváltozott és áthelyezésre került: %s → %s';
$lang['delete']      = 'A Move Plugin (áthelyezési bővítmény) által törölve.';
$lang['linkchange']  = 'Hivatkozások átírva áthelyezés miatt.';

// progress view
$lang['intro']        = 'Az áthelyezési folyamat még nem kezdődött el!';
$lang['preview']      = 'Tekintsd át a függőben lévő változtatásokat.';
$lang['inexecution']  = 'Egy korábbi áthelyezés félbeszakadt. Az alábbi gombokkal dönthetsz a sorsáról.';
$lang['btn_start']    = 'Indítás';
$lang['btn_continue'] = 'Folytatás';
$lang['btn_retry']    = 'Elem újrapróbálása';
$lang['btn_skip']     = 'Elem kihagyása';
$lang['btn_abort']    = 'Megszakítás';

// Form labels
$lang['legend']               = 'Oldal vagy névtér áthelyezése';
$lang['movepage']             = 'Oldal áthelyezése';
$lang['movens']               = 'Névtér áthelyezése';
$lang['dst']                  = 'Új név:';
$lang['content_to_move']      = 'Áthelyezendő tartalom:';
$lang['autoskip']             = 'Hibák figyelmen kívül hagyása és nem áthelyezhető fájlok kihagyása';
$lang['autorewrite']          = 'Hivatkozások átírása áthelyezés után';
$lang['move_pages']           = 'Oldalak';
$lang['move_media']           = 'Fájlok';
$lang['move_media_and_pages'] = 'Oldalak és fájlok';
$lang['nodst']                = 'Nincs megadva új név';
$lang['noaction']             = 'Nincs meghatározva áthelyezési művelet';

// Rename feature
$lang['renamepage']       = 'Oldal átnevezése';
$lang['cantrename']       = 'Ezt az oldalt jelenleg nem lehet átnevezni. Próbáld újra később.';
$lang['notallowed']       = 'Nincs jogosultságod az oldalak és fájlok átnevezéséhez.';
$lang['js']['rename']     = 'Átnevezés';
$lang['js']['cancel']     = 'Mégse';
$lang['js']['newname']    = 'Új név:';
$lang['js']['inprogress'] = 'Oldal átnevezése és hivatkozások átírása folyamatban…';
$lang['js']['complete']   = 'Az áthelyezési művelet sikeresen befejeződött.';

// Tree Manager
$lang['root']             = '[Gyökérnévtér]';
$lang['noscript']         = 'Ehhez a funkcióhoz JavaScript szükséges';
$lang['moveinprogress']   = 'Egy másik áthelyezési művelet épp függőben van. Próbáld újra később.';
$lang['js']['renameitem'] = 'Elem átnevezése';
$lang['js']['add']        = 'Új névtér létrehozása';
$lang['js']['duplicate']  = 'A(z) „%s” már létezik ebben a névtérben.';

// Media Manager
$lang['js']['moveButton'] = 'Fájl áthelyezése';
$lang['js']['dialogIntro'] = 'Add meg a célhelyet. A névtér megváltozhat, de a fájlkiterjesztés nem.';
