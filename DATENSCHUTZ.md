# Higawari: technische Grundlage für den Datenschutztext

(English version: [PRIVACY.md](PRIVACY.md). Die Optionen heißen
`sayamaapps.higawari.*`; die Namen stehen im [README](README.md#configuration).)

Dieser Baustein beschreibt die Implementierung. Rechtsgrundlage, Interessenabwägung
und die Formulierung müssen vor Veröffentlichung für den konkreten Betrieb fachlich
geprüft und ergänzt werden. Der Text ist keine Rechtsberatung.

## Tageskarte — technischer Textbaustein

Die Tageskarte auf unserer Website zeigt die Gerichte des Tages. Ob sie heute gilt,
entscheidet unser Webserver anhand des Datums, das wir eingetragen haben. Ihr Browser
baut dafür **keine Verbindung zu einem anderen Dienst** auf: Es wird kein Skript,
keine Schriftart, keine Einbettung und kein Bild von einem fremden Server nachgeladen.

Das eingesetzte Modul setzt **keine Cookies**, verwendet **keinen Browser-Speicher**
(etwa Local Storage oder Session Storage) und erhebt **keine Daten über Besucherinnen
und Besucher** dieser Website. Es liest weder Ihre IP-Adresse noch Ihren Browser-Typ.

Die Daten sind Angaben zu unserem Angebot: Gerichte, Preise und kurze Hinweise, die wir
selbst eintragen.

## Hinweise für den Betreiber

- **Keine Übertragung durch das Modul.** Das Modul überträgt keine Daten an Dritte.
  Hosting und die Zugriffsprotokolle des Webservers liegen außerhalb des Moduls und
  sind in der allgemeinen Datenschutzerklärung der Website zu beschreiben.
- **Keine personenbezogenen Angaben in den Feldern.** Die Felder „Gericht“,
  „Weitere Zeilen“ und „Kennzeichnungen“ sind für Angaben zum Angebot gedacht.
- **Kein Änderungsweg über das Modul.** Änderungen erfolgen im Panel oder über ein
  separates Modul, dessen Datenschutz eigens zu beschreiben ist.
