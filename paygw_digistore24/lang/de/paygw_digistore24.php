<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Digistore24';
$string['pluginname_desc'] = 'Das Digistore24-Plugin ermöglicht Zahlungen über Digistore24.';
$string['gatewayname'] = 'Digistore24';
$string['gatewaydescription'] = 'Digistore24 ist ein autorisierter Zahlungsanbieter.';
$string['gatewaycannotbeenabled'] = 'Digistore24 kann nicht aktiviert werden. Bitte zuerst API-Schlüssel, IPN-Passphrase und Standard-Produkt-ID in den Plugin-Einstellungen hinterlegen.';

$string['brandname'] = 'Markenname';
$string['brandname_help'] = 'Optionaler Markenname, der dem Käufer auf der Digistore24-Bestellseite angezeigt wird.';

$string['apikey'] = 'API-Schlüssel';
$string['apikey_desc'] = 'Voller Digistore24-API-Schlüssel. Wird als Geheimnis gespeichert; wird für createBuyUrl benötigt.';
$string['ipn_passphrase'] = 'IPN-Passphrase';
$string['ipn_passphrase_desc'] = 'Gemeinsame SHA-Passphrase zur Validierung eingehender IPN-Aufrufe. Muss mit der in deinem Digistore24-Anbieterkonto konfigurierten IPN-Passphrase übereinstimmen.';
$string['default_product_id'] = 'Standard-Digistore24-Produkt-ID';
$string['default_product_id_desc'] = 'Wird für jedes zahlungspflichtige Element verwendet, das auf der Mapping-Seite kein eigenes Override hat.';
$string['grace_period_days'] = 'Kulanzzeitraum für Abos (Tage)';
$string['grace_period_days_desc'] = 'Nach einer fehlgeschlagenen Verlängerung oder einer Kündigung endet der Zugang so viele Tage nach dem Ende des bezahlten Zeitraums.';
$string['test_mode'] = 'Testmodus';
$string['test_mode_desc'] = 'Wenn aktiviert, werden Digistore24-Testkäufe erwartet. Test-IPNs werden trotzdem normal validiert und protokolliert.';

$string['links_heading'] = 'Endpunkte';
$string['links_desc'] = '
<dl>
  <dt>IPN-URL (in Digistore24 hinterlegen)</dt><dd><code>{$a->callback}</code></dd>
  <dt>Produkt-Mapping</dt><dd><a href="{$a->mapping}">{$a->mapping}</a></dd>
  <dt>Transaktionsbericht</dt><dd><a href="{$a->report}">{$a->report}</a></dd>
</dl>';

$string['mapping_pagetitle'] = 'Digistore24-Produkt-Mapping';
$string['mapping_intro'] = 'Verknüpfe ein bestimmtes Moodle-Zahlungselement (Kurseinschreibung, Aktivität, Custom-Komponente) mit einer Digistore24-Produkt-ID. Nicht zugeordnete Elemente nutzen die Standard-Produkt-ID.';
$string['mapping_addnew'] = 'Mapping anlegen';
$string['mapping_component'] = 'Komponente';
$string['mapping_paymentarea'] = 'Payment area';
$string['mapping_itemid'] = 'Item-ID';
$string['mapping_productid'] = 'Digistore24-Produkt-ID';
$string['mapping_actions'] = 'Aktionen';
$string['mapping_edit'] = 'Mapping bearbeiten';
$string['mapping_delete_confirm'] = 'Dieses Mapping löschen? Die Elemente fallen dann auf die Standard-Produkt-ID zurück.';
$string['mapping_saved'] = 'Mapping gespeichert';
$string['mapping_deleted'] = 'Mapping gelöscht';
$string['mapping_no_payment_account'] = 'Kein Zahlungskonto nutzt das Digistore24-Gateway. Aktiviere Digistore24 zuerst auf einem Zahlungskonto.';

$string['report_pagetitle'] = 'Digistore24-Transaktionen';
$string['report_paymentid'] = 'Zahlung';
$string['report_user'] = 'Nutzer:in';
$string['report_component'] = 'Komponente';
$string['report_itemid'] = 'Item';
$string['report_amount'] = 'Betrag';
$string['report_status'] = 'Status';
$string['report_event'] = 'Ereignis';
$string['report_transaction_id'] = 'Digistore24-Transaktion';
$string['report_period_end'] = 'Periodenende';
$string['report_timecreated'] = 'Erstellt';
$string['report_filter_status'] = 'Statusfilter';
$string['report_status_all'] = 'Alle';
$string['report_status_delivered'] = 'Geliefert';
$string['report_status_reversed'] = 'Storniert';
$string['report_status_failed'] = 'Fehlgeschlagen';

$string['redirecting'] = 'Weiterleitung zu Digistore24…';
$string['redirect_failed'] = 'Digistore24-Checkout konnte nicht gestartet werden: {$a}';
$string['internalerror'] = 'Ein interner Fehler ist aufgetreten. Die Zahlung wurde nicht gestartet.';
$string['no_product_mapping'] = 'Für dieses Element ist kein Digistore24-Produkt konfiguriert und auch keine Standard-Produkt-ID gesetzt. Bitte wende dich an die Administration.';

$string['return_pagetitle'] = 'Zahlungsbestätigung';
$string['return_pending'] = 'Deine Zahlung ist eingegangen und wird abgeschlossen. Die Seite aktualisiert sich automatisch.';
$string['return_paid'] = 'Vielen Dank. Deine Zahlung wurde bestätigt und der Zugang wurde freigeschaltet.';
$string['return_failed'] = 'Diese Zahlung konnte nicht bestätigt werden. Falls du belastet wurdest, wende dich bitte an den Support.';
$string['return_continue'] = 'Weiter';

$string['ipn_ok'] = 'OK';
$string['ipn_invalid_signature'] = 'Ungültige IPN-Signatur';
$string['ipn_unknown_payment'] = 'Unbekannte Zahlungs-ID im custom-Feld';
$string['ipn_amount_mismatch'] = 'Betrag oder Währung der IPN passen nicht zur Moodle-Zahlung';

$string['digistore24:managemapping'] = 'Digistore24-Produkt-Mappings verwalten';
$string['digistore24:viewreport'] = 'Digistore24-Transaktionsbericht ansehen';

$string['task_check_subscription_endings'] = 'Zugang für gekündigte oder fehlgeschlagene Digistore24-Abos nach Ablauf der Kulanzzeit beenden';

$string['event_payment_reversed'] = 'Digistore24-Zahlung storniert';

$string['privacy:metadata:paygw_digistore24_txn'] = 'Speichert Digistore24-Transaktionsreferenzen zu einer Moodle-Zahlung.';
$string['privacy:metadata:paygw_digistore24_txn:paymentid'] = 'Moodle-Zahlungs-ID, zu der die Transaktion gehört.';
$string['privacy:metadata:paygw_digistore24_txn:transaction_id'] = 'Digistore24-Transaktions-ID.';
$string['privacy:metadata:paygw_digistore24_txn:order_id'] = 'Digistore24-Bestell-ID.';
$string['privacy:metadata:paygw_digistore24_txn:status'] = 'Status der Transaktion (delivered, reversed, failed).';
$string['privacy:metadata:paygw_digistore24_txn:timecreated'] = 'Zeitpunkt der Erstellung.';

$string['privacy:metadata:digistore24'] = 'Zur Zahlungsabwicklung werden Nutzerdaten an Digistore24 übertragen.';
$string['privacy:metadata:digistore24:email'] = 'Die E-Mail-Adresse wird an Digistore24 gesendet, um den Käufer zu identifizieren.';
$string['privacy:metadata:digistore24:firstname'] = 'Der Vorname wird an Digistore24 gesendet.';
$string['privacy:metadata:digistore24:lastname'] = 'Der Nachname wird an Digistore24 gesendet.';
$string['privacy:metadata:digistore24:country'] = 'Das Land wird an Digistore24 gesendet (für die Steuerberechnung).';
$string['privacy:metadata:digistore24:amount'] = 'Der zu zahlende Betrag wird an Digistore24 gesendet.';
$string['privacy:metadata:digistore24:currency'] = 'Die Währung der Zahlung wird an Digistore24 gesendet.';
$string['privacy:metadata:digistore24:product_id'] = 'Die Digistore24-Produkt-ID wird in der Checkout-URL übergeben.';
