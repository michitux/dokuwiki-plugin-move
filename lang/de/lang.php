<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author picsar
 * @author e-dschungel <github@e-dschungel.de>
 * @author F. Mueller-Donath <j.felix@mueller-donath.de>
 */
$lang['menu']                  = 'Seite/Namensraum verschieben/umbenennen...';
$lang['inprogress']            = '(Verschiebung läuft gerade)';
$lang['treelink']              = 'Alternativ zu dieser einfachen Form können komplexere Umstrukturierungen des Wikis mit dem <a href="%s">baumbasierten Move-Manager</a> durchgeführt werden.';
$lang['notexist']              = 'Die Seite %s existiert nicht.';
$lang['norights']              = 'Sie haben unzureichende Rechte um %s zu bearbeiten.';
$lang['filelocked']            = 'Die Seite %s ist gesperrt - versuchen Sie es später noch einmal.';
$lang['notchanged']            = 'Kein Ziel für Seite %s angegeben (Ort bleibt unverändert).';
$lang['exists']                = 'Seite %s kann nicht nach %s verschoben werden, da das Ziel bereits existiert.';
$lang['notargetperms']         = 'Sie haben nicht die Berechtigung, die Seite %s anzulegen.';
$lang['medianotexist']         = 'Die Mediendatei %s existiert nicht';
$lang['nomediarights']         = 'Sie haben unzureichende Rechte, um die Mediendatei %s zu löschen.';
$lang['medianotchanged']       = 'Kein Ziel für Seite %s angegeben (Ort bleibt unverändert).';
$lang['mediaexists']           = 'Mediendatei %s kann nicht nach %s verschoben werden, da das Ziel bereits existiert.';
$lang['nomediatargetperms']    = 'Sie haben nicht die Berechtigung, die Mediendatei %s anzulegen.';
$lang['extensionchange']       = 'Die Dateierweiterungen der alten und der neuen Datei sind unterschiedlich.';
$lang['indexerror']            = 'Fehler während der Aktualisierung des Suchindexes %s';
$lang['metamoveerror']         = 'Die Metadateien der Seite %s konnten nicht verschoben werden';
$lang['atticmoveerror']        = 'Die Attic-Dateien der Seite %s konnten nicht verschoben werden. Bitte verschieben Sie sie manuell.';
$lang['mediametamoveerror']    = 'Die Metadateien der Mediendatei %s konnten nicht verschoben werden';
$lang['mediamoveerror']        = 'Verschieben der Mediendatei %s fehlgeschlagen';
$lang['mediaatticmoveerror']   = 'Die Attic-Dateien der Mediendatei %s konnten nicht verschoben werden. Bitte verschieben Sie sie manuell.';
$lang['renamed']               = 'Seitename wurde von %s auf %s geändert';
$lang['moved']                 = 'Seite von %s nach %s verschoben';
$lang['move_rename']           = 'Seite von %s nach %s verschoben und umbenannt';
$lang['delete']                = 'Gelöscht durch das move Plugin';
$lang['linkchange']            = 'Links angepasst, weil Seiten im Wiki verschoben wurden';
$lang['intro']                 = 'Die Verschiebung wurde noch nicht gestartet!';
$lang['preview']               = 'Vorschau der Änderungen, die ausgeführt werden sollen ';
$lang['inexecution']           = 'Eine vorherige Verschiebung wurde nicht beendet - benutzen Sie die Knöpfe unten um die Verschiebung fortzusetzen oder abzubrechen. ';
$lang['btn_start']             = 'Start';
$lang['btn_continue']          = 'Fortsetzen';
$lang['btn_retry']             = 'Element wiederholen';
$lang['btn_skip']              = 'Element überspringen';
$lang['btn_abort']             = 'Abbrechen';
$lang['legend']                = 'Aktuelle Seite oder aktueller Namensraum';
$lang['movepage']              = 'Seite verschieben';
$lang['movens']                = 'Namensraum verschieben';
$lang['dst']                   = 'Neuer Name:';
$lang['content_to_move']       = 'Zu verschiebender Inhalt:';
$lang['autoskip']              = 'Fehler ignorieren und Seiten oder Dateien, die nicht verschoben werden können, überspringen';
$lang['autorewrite']           = 'Links anpassen, nachdem die Verschiebung abgeschlossen wurde';
$lang['move_pages']            = 'Seiten';
$lang['move_media']            = 'Mediendateien';
$lang['move_media_and_pages']  = 'Seiten und Mediendateien';
$lang['nodst']                 = 'Kein neuer Name angegeben';
$lang['noaction']              = 'Es wurden keine Verschiebungen angegeben';
$lang['renamepage']            = 'Seite umbenennen';
$lang['notallowed']            = 'Sie haben unzureichende Rechte, um Seiten oder Medien umzubenennen.';
$lang['cantrename']            = 'Auf die Seite kann zur Zeit nicht zugegriffen werden - versuchen Sie es später noch einmal.';
$lang['js']['rename']          = 'Umbenennen';
$lang['js']['cancel']          = 'Abbrechen';
$lang['js']['newname']         = 'Neuer Name:';
$lang['js']['inprogress']      = 'Benenne Seite um und passe Links an...';
$lang['js']['complete']        = 'Verschieben abgeschlossen.';
$lang['js']['renameitem']      = 'Dieses Element umbenennen';
$lang['js']['add']             = 'Neuen Namensraum erstellen';
$lang['js']['duplicate']       = 'Entschuldigung, "%s" existiert in diesem Namensraum bereits. ';
$lang['root']                  = '[Oberster Namensraum]';
$lang['noscript']              = 'Dieses Feature benötigt JavaScript.';
$lang['moveinprogress']        = 'Eine andere Verschiebeoperation läuft momentan, Sie können dieses Tool gerade nicht benutzen.';

// Media Manager
$lang['js']['moveButton']       = 'Datei verschieben / umbenennen';
$lang['js']['dialogIntro']      = 'Neuer Dateiname. Sie können den Namensraum ändern, aber nicht die Dateierweiterung.';
