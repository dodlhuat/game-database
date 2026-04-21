# Testfälle

Verwaltungsplattform für eine Brettspiel-Leihbibliothek.

---

## Ausleihe

### TC-01 — Verfügbares Spiel ausleihen

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Ein Spiel mit mindestens einer ausleihbaren Kopie ist vorhanden
- Das freie Token-Guthaben reicht für Leihgebühr und Kaution zusammen aus

**Schritte**
1. Detailseite des Spiels öffnen
2. Auf „Ausleihen" klicken

**Erwartetes Ergebnis**
- Die Ausleihe wird erstellt und erscheint in den aktiven Ausleihen mit einem Rückgabedatum
- Die Leihgebühr wird sofort vom Token-Guthaben abgezogen (dauerhaft weg)
- Der Kautionsbetrag wird auf dem Konto blockiert (reserviert, nicht verbraucht)

---

### TC-02 — Ausleihe nicht möglich, wenn alle Kopien vergeben sind

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Ein Spiel ist vorhanden, aber alle Kopien sind aktuell ausgeliehen

**Schritte**
1. Detailseite des Spiels öffnen

**Erwartetes Ergebnis**
- Kein „Ausleihen"-Button ist sichtbar
- Eine Option zur Eintragung in die Reservierungsliste wird angezeigt

---

### TC-03 — Ausleihe nicht möglich ohne aktive Mitgliedschaft

**Voraussetzungen**
- Als registrierter Benutzer eingeloggt, dessen Mitgliedschaft abgelaufen ist

**Schritte**
1. Detailseite eines beliebigen Spiels öffnen

**Erwartetes Ergebnis**
- Der „Ausleihen"-Button ist nicht verfügbar
- Ein Hinweis wird angezeigt, dass eine aktive Mitgliedschaft erforderlich ist

---

### TC-04 — Ausleihe nicht möglich bei zu wenig Token

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Das Token-Guthaben ist niedriger als die Summe aus Leihgebühr und Kaution für die gewählte Kopie

**Schritte**
1. Detailseite des Spiels öffnen

**Erwartetes Ergebnis**
- Der „Ausleihen"-Button ist nicht verfügbar
- Ein Hinweis wird angezeigt, der den erforderlichen Gesamtbetrag (Leihgebühr + Kaution) nennt und das Guthaben als unzureichend ausweist

---

## Rückgabe

### TC-05 — Spiel erfolgreich zurückgeben

**Voraussetzungen**
- Als Mitglied eingeloggt
- Eine aktive Ausleihe ist vorhanden

**Schritte**
1. Ausleihe in der Übersicht öffnen
2. Auf „Zurückgeben" klicken
3. Zustand der Kopie bei Rückgabe angeben

**Erwartetes Ergebnis**
- Die Ausleihe wechselt in den Status „Zurückgegeben"
- Die Kopie wechselt in den Status „Überprüfen" (REVIEW) und ist vorerst nicht mehr ausleihbar
- Die Kaution bleibt bis zur Admin-Freigabe blockiert

---

### TC-06 — Rückgabe einer überfälligen Ausleihe

**Voraussetzungen**
- Als Mitglied eingeloggt
- Eine Ausleihe mit Status „Überfällig" ist vorhanden

**Schritte**
1. Ausleihe in der Übersicht öffnen
2. Auf „Zurückgeben" klicken
3. Zustand der Kopie angeben

**Erwartetes Ergebnis**
- Die Rückgabe funktioniert wie bei einer normalen Ausleihe
- Die Ausleihe wechselt in den Status „Zurückgegeben"
- Die Kopie wechselt in den Status „Überprüfen"

---

## Verlängerung

### TC-07 — Verlängerung einer aktiven Ausleihe beantragen

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Eine aktive Ausleihe ist vorhanden
- Mindestens 1 freies Token ist vorhanden
- Das Maximum an Verlängerungen ist noch nicht erreicht
- Kein offener Verlängerungsantrag für diese Ausleihe vorhanden

**Schritte**
1. Ausleihe in der Übersicht öffnen
2. Gewünschtes neues Rückgabedatum wählen
3. Verlängerung beantragen

**Erwartetes Ergebnis**
- Der Antrag wird erstellt und erscheint als „Ausstehend"
- 1 Token wird sofort abgezogen
- Ein weiterer Antrag für dieselbe Ausleihe ist nicht mehr möglich, solange dieser offen ist

---

### TC-08 — Verlängerung nicht möglich bei bereits offenem Antrag

**Voraussetzungen**
- Als Mitglied eingeloggt
- Eine aktive Ausleihe mit einem bereits ausstehenden Verlängerungsantrag ist vorhanden

**Schritte**
1. Erneut eine Verlängerung für dieselbe Ausleihe beantragen

**Erwartetes Ergebnis**
- Eine Fehlermeldung erscheint, dass bereits ein offener Antrag existiert
- Kein weiterer Antrag wird erstellt

---

### TC-09 — Verlängerung nicht möglich wenn Maximum erreicht

**Voraussetzungen**
- Als Mitglied eingeloggt
- Eine Ausleihe, die bereits die maximale Anzahl an Verlängerungen ausgeschöpft hat

**Schritte**
1. Verlängerung für diese Ausleihe beantragen

**Erwartetes Ergebnis**
- Eine Fehlermeldung erscheint, dass die maximale Anzahl an Verlängerungen erreicht ist
- Kein weiterer Antrag wird erstellt

---

### TC-10 — Admin genehmigt Verlängerungsantrag

**Voraussetzungen**
- Als Admin eingeloggt
- Ein ausstehender Verlängerungsantrag ist vorhanden

**Schritte**
1. Verlängerungsanträge in der Admin-Übersicht öffnen
2. Antrag auswählen und genehmigen

**Erwartetes Ergebnis**
- Der Antrag wechselt in den Status „Genehmigt"
- Das Rückgabedatum der Ausleihe wird auf das beantragte Datum gesetzt
- Die Ausleihe wechselt in den Status „Verlängert"

---

### TC-11 — Admin lehnt Verlängerungsantrag ab

**Voraussetzungen**
- Als Admin eingeloggt
- Ein ausstehender Verlängerungsantrag ist vorhanden

**Schritte**
1. Verlängerungsanträge in der Admin-Übersicht öffnen
2. Antrag auswählen und ablehnen

**Erwartetes Ergebnis**
- Der Antrag wechselt in den Status „Abgelehnt"
- Das Rückgabedatum der Ausleihe bleibt unverändert
- Der Status der Ausleihe bleibt unverändert

---

## Kopienprüfung nach Rückgabe

### TC-12 — Admin gibt Kopie nach Rückgabe frei

**Voraussetzungen**
- Als Admin eingeloggt
- Eine Kopie befindet sich im Status „Überprüfen" (nach Rückgabe)

**Schritte**
1. Kopie in der Admin-Verwaltung öffnen
2. Kopie freigeben

**Erwartetes Ergebnis**
- Die Kopie erhält basierend auf der Ausleihanzahl automatisch einen neuen Zustand und ist wieder ausleihbar
- Die blockierte Kaution des letzten Ausleihers wird freigegeben
- Der Ausleiher erhält eine Benachrichtigung über die Kautionsfreigabe
- Falls eine Reservierung für dieses Spiel vorliegt, wird der erste Wartende benachrichtigt

---

### TC-13 — Admin markiert Kopie als beschädigt

**Voraussetzungen**
- Als Admin eingeloggt
- Eine Kopie befindet sich im Status „Überprüfen" (nach Rückgabe)

**Schritte**
1. Kopie in der Admin-Verwaltung öffnen
2. Kopie als beschädigt markieren (optional: Notiz hinzufügen)

**Erwartetes Ergebnis**
- Die Kopie wechselt in den Status „Beschädigt" und ist nicht mehr ausleihbar
- Die blockierte Kaution des letzten Ausleihers wird einbehalten (nicht zurückgegeben)
- Der Ausleiher erhält eine Benachrichtigung, dass die Kaution einbehalten wurde

---

## Reservierung

### TC-14 — Reservierung für ausgebuchtes Spiel eintragen

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Ein Spiel ist vorhanden, aber alle Kopien sind aktuell ausgeliehen

**Schritte**
1. Detailseite des Spiels öffnen
2. In die Reservierungsliste eintragen

**Erwartetes Ergebnis**
- Die Reservierung wird erstellt und die eigene Position in der Warteschlange wird angezeigt

---

### TC-15 — Benachrichtigung wenn reserviertes Spiel verfügbar wird

**Voraussetzungen**
- Als Mitglied in der Reservierungsliste für ein Spiel eingetragen
- Ein Admin gibt eine zurückgegebene Kopie dieses Spiels frei

**Schritte**
- (Keine eigene Aktion erforderlich — wird durch Admin-Freigabe ausgelöst)

**Erwartetes Ergebnis**
- Eine Benachrichtigung wird an den ersten Wartenden gesendet, dass das Spiel wieder verfügbar ist

---

### TC-16 — Reservierung stornieren

**Voraussetzungen**
- Als Mitglied eingeloggt
- Eine aktive Reservierung ist vorhanden

**Schritte**
1. Reservierungen in der eigenen Übersicht öffnen
2. Reservierung stornieren

**Erwartetes Ergebnis**
- Die Reservierung wird entfernt
- Die eigene Position in der Warteschlange entfällt

---

## Mitgliedschaft

### TC-17 — Mitgliedschaft abschließen

**Voraussetzungen**
- Als registrierter Benutzer (kein Mitglied) eingeloggt

**Schritte**
1. Mitgliedschaft beantragen
2. Adresse angeben und bestätigen

**Erwartetes Ergebnis**
- Der Benutzer erhält die Rolle „Mitglied"
- Die Mitgliedschaft läuft ab heute ein Jahr
- 20 Token werden dem Konto gutgeschrieben
- Eine Willkommens-E-Mail wird versendet

---

### TC-18 — Mitgliedschaft verlängern

**Voraussetzungen**
- Als Mitglied eingeloggt
- Die Mitgliedschaft läuft in weniger als 3 Monaten ab

**Schritte**
1. Mitgliedschaft verlängern

**Erwartetes Ergebnis**
- Die Mitgliedschaft wird um ein Jahr ab dem bisherigen Ablaufdatum verlängert
- 20 Token werden dem Konto gutgeschrieben

---

### TC-19 — Verlängerung zu früh nicht möglich

**Voraussetzungen**
- Als Mitglied eingeloggt
- Die Mitgliedschaft läuft erst in mehr als 3 Monaten ab

**Schritte**
1. Mitgliedschaft verlängern

**Erwartetes Ergebnis**
- Eine Fehlermeldung erscheint, dass die Verlängerung erst 3 Monate vor Ablauf möglich ist
- Die Mitgliedschaft bleibt unverändert

---

## Token

### TC-20 — Token kaufen

**Voraussetzungen**
- Als Mitglied eingeloggt

**Schritte**
1. Token-Kauf öffnen
2. Einen der verfügbaren Beträge wählen (20, 30 oder 40 Token)
3. Kauf bestätigen

**Erwartetes Ergebnis**
- Der gewählte Betrag wird dem Token-Guthaben gutgeschrieben

---

### TC-21 — Ungültiger Token-Betrag wird abgelehnt

**Voraussetzungen**
- Als Mitglied eingeloggt

**Schritte**
1. Token-Kauf mit einem Betrag außerhalb der erlaubten Werte versuchen (z. B. 25 Token)

**Erwartetes Ergebnis**
- Eine Fehlermeldung erscheint
- Das Guthaben bleibt unverändert

---

### TC-22 — Token-Transaktionen einsehen

**Voraussetzungen**
- Als Mitglied eingeloggt mit mindestens einer Token-Transaktion in der Vergangenheit

**Schritte**
1. Token-Übersicht öffnen

**Erwartetes Ergebnis**
- Alle Transaktionen werden aufgelistet (Leihgebühren, Kautionen, Käufe, Freigaben, Einbehaltungen)
- Für jede Transaktion sind Betrag, Typ und Beschreibung sichtbar

---

## Pakete

### TC-23 — Spielpaket ausleihen

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Ein aktives Paket ist vorhanden, bei dem alle enthaltenen Spiele eine verfügbare Kopie haben
- Das freie Token-Guthaben reicht für Leihgebühr und Gesamtkaution aus

**Schritte**
1. Paketdetailseite öffnen
2. Auf „Paket ausleihen" klicken

**Erwartetes Ergebnis**
- Für jedes Spiel im Paket wird eine eigene Ausleihe erstellt
- Die Leihgebühr wird einmalig abgezogen (gilt für das gesamte Paket)
- Die summierten Kautionen aller Kopien werden blockiert

---

### TC-24 — Paket nicht ausleihbar wenn ein Spiel fehlt

**Voraussetzungen**
- Als Mitglied mit aktiver Mitgliedschaft eingeloggt
- Ein Paket ist vorhanden, bei dem mindestens ein enthaltenes Spiel keine verfügbare Kopie hat

**Schritte**
1. Paketdetailseite öffnen
2. Auf „Paket ausleihen" klicken

**Erwartetes Ergebnis**
- Eine Fehlermeldung erscheint, welches Spiel nicht verfügbar ist
- Es wird keine Ausleihe erstellt

---

### TC-25 — Spielpaket zurückgeben

**Voraussetzungen**
- Als Mitglied eingeloggt
- Ein aktives Paket-Ausleihe ist vorhanden

**Schritte**
1. Paket-Ausleihe in der Übersicht öffnen
2. Auf „Paket zurückgeben" klicken

**Erwartetes Ergebnis**
- Alle Einzelausleihen des Pakets wechseln in den Status „Zurückgegeben"
- Alle betroffenen Kopien wechseln in den Status „Überprüfen"
- Das Paket-Ausleihe wechselt in den Status „Zurückgegeben"

---

## Bewertungen & Favoriten

### TC-26 — Spiel bewerten

**Voraussetzungen**
- Als Mitglied eingeloggt
- Das Spiel wurde mindestens einmal ausgeliehen und zurückgegeben

**Schritte**
1. Detailseite des Spiels öffnen
2. Bewertung (Sterne) und optionalen Kommentar abgeben

**Erwartetes Ergebnis**
- Die Bewertung erscheint auf der Detailseite des Spiels

---

### TC-27 — Spiel zu Favoriten hinzufügen und entfernen

**Voraussetzungen**
- Als Mitglied eingeloggt

**Schritte**
1. Detailseite eines Spiels öffnen
2. Spiel zu Favoriten hinzufügen
3. Favoriten-Übersicht öffnen und prüfen, ob das Spiel erscheint
4. Spiel aus Favoriten entfernen

**Erwartetes Ergebnis**
- Nach Schritt 2 erscheint das Spiel in der Favoriten-Übersicht
- Nach Schritt 4 ist das Spiel nicht mehr in der Favoriten-Übersicht

---

## Admin — Benutzerverwaltung

### TC-28 — Benutzer freischalten

**Voraussetzungen**
- Als Admin eingeloggt
- Ein Benutzer mit Status „Ausstehend" ist vorhanden

**Schritte**
1. Benutzerverwaltung öffnen
2. Benutzer auswählen und freischalten

**Erwartetes Ergebnis**
- Der Benutzer erhält den Status „Aktiv"
- Der Benutzer erhält eine Benachrichtigung über die Freischaltung

---

### TC-29 — Benutzer ablehnen

**Voraussetzungen**
- Als Admin eingeloggt
- Ein Benutzer mit Status „Ausstehend" ist vorhanden

**Schritte**
1. Benutzerverwaltung öffnen
2. Benutzer auswählen, Ablehnungsgrund angeben und ablehnen

**Erwartetes Ergebnis**
- Der Benutzer erhält den Status „Abgelehnt"
- Der Benutzer erhält eine Benachrichtigung mit dem angegebenen Grund

---

### TC-30 — Benutzer sperren

**Voraussetzungen**
- Als Admin eingeloggt
- Ein aktiver Benutzer ist vorhanden

**Schritte**
1. Benutzerverwaltung öffnen
2. Benutzer auswählen und sperren

**Erwartetes Ergebnis**
- Der Benutzer erhält den Status „Gesperrt"
- Der Benutzer kann sich nicht mehr einloggen

---

## Admin — Token

### TC-31 — Token manuell an Benutzer vergeben

**Voraussetzungen**
- Als Admin eingeloggt

**Schritte**
1. Benutzer in der Verwaltung öffnen
2. Token-Betrag manuell eintragen und gutschreiben

**Erwartetes Ergebnis**
- Das Token-Guthaben des Benutzers erhöht sich um den eingetragenen Betrag
- Die Transaktion erscheint in der Token-Historie des Benutzers

---

## Admin — Ausleihe

### TC-32 — Ausleihe als überfällig markieren

**Voraussetzungen**
- Als Admin eingeloggt
- Eine aktive Ausleihe, deren Rückgabedatum überschritten ist

**Schritte**
1. Ausleihe in der Admin-Übersicht öffnen
2. Als überfällig markieren

**Erwartetes Ergebnis**
- Die Ausleihe wechselt in den Status „Überfällig"

---

### TC-33 — Rückgabe durch Admin erfassen

**Voraussetzungen**
- Als Admin eingeloggt
- Eine aktive, verlängerte oder überfällige Ausleihe ist vorhanden

**Schritte**
1. Ausleihe in der Admin-Übersicht öffnen
2. Rückgabe erfassen und Zustand der Kopie angeben

**Erwartetes Ergebnis**
- Die Ausleihe wechselt in den Status „Zurückgegeben"
- Die Kopie wechselt in den Status „Überprüfen"
