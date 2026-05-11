# Google Search Console & Indexing API — Einrichtung

Damit Google Änderungen am SEO-Content schneller aufnimmt (statt 1–3 Wochen),
nutzt das Backoffice drei Mechanismen:

1. **`Last-Modified`-HTTP-Header** auf allen öffentlichen Seiten
   (automatisch via `EmitsSeoHeaders`-Trait, erfordert kein Setup).
2. **Google Indexing API** — programmatisches Pingen analog zu IndexNow:
   automatisch bei jeder Speicherung im SEO-Editor und manuell auf Knopfdruck
   ("Google reindex"-Button im SEO-Editor pingt alle Sprachen einer Seite
   in einem Schritt).
3. **Search Console** — manuelle Sitemap-Verwaltung und URL Inspection
   (für Sonderfälle bei dringenden Änderungen).

> **Caveat zur Indexing API:** Offiziell unterstützt Google sie nur für
> `JobPosting` und `BroadcastEvent`. Andere Seitentypen werden in der Praxis
> oft akzeptiert, sind aber außerhalb der dokumentierten Spezifikation —
> Google kann die Verarbeitung jederzeit einstellen oder rate-limitieren.
> Wir vertrauen primär auf Last-Modified + Search Console; die Indexing API
> ist ein Bonus-Signal.

---

## 1. Search Console Property einrichten (einmalig)

1. https://search.google.com/search-console öffnen (mit dem Google-Account, der
   die Property verwalten soll).
2. **Property hinzufügen** → "Domain" wählen → `tnduniverse.com` eingeben.
   - Falls "Domain" nicht möglich (z.B. kein DNS-Zugriff): "URL-Präfix" wählen
     mit `https://tnduniverse.com/` und entsprechend `GOOGLE_SC_RESOURCE_ID`
     anpassen.
3. **Verifizieren** via DNS-TXT-Record beim Domain-Provider (Plesk/Registrar).
   Den von Google angezeigten Record im DNS hinterlegen, ~5 Min warten,
   "Bestätigen" klicken.
4. Im SC-Menü links: **Sitemaps** → URL eintragen: `sitemap.xml` → "Senden".
   Status nach 24–48h prüfen ("Erfolgreich").
Damit ist Search Console eingerichtet. Für Sonderfälle (z.B. dringend zu
indexierende Einzel-URL) lässt sich dort weiterhin manuell "Indexierung
beantragen" über die URL Inspection nutzen — der Standard-Workflow läuft
aber komplett über die Indexing API (siehe nächster Abschnitt).

---

## 2. Google Indexing API aktivieren (optional)

### 2a. Google Cloud Setup

1. https://console.cloud.google.com → Projekt wählen oder neu anlegen
   ("tnd-universe-indexing" o.ä.).
2. **APIs & Services → Library** → folgende APIs aktivieren:
   - "Indexing API"
   - "Google Search Console API"
3. **APIs & Services → Credentials** → "Service Account erstellen"
   - Name: z.B. `tnd-indexing-bot`
   - Rollen: keine zusätzlichen nötig (Berechtigung kommt aus Search Console)
   - Nach Erstellen: Service-Account anklicken → Tab "Keys" → "Add Key" →
     "Create new key" → JSON → Download.

### 2b. Service Account in Search Console berechtigen

1. Search Console öffnen → die in Schritt 1 angelegte Property auswählen.
2. **Settings (Zahnrad) → Users and permissions** → "Add user".
3. E-Mail des Service Accounts eintragen (Format
   `tnd-indexing-bot@<projekt-id>.iam.gserviceaccount.com`).
4. **Permission: Owner** wählen — Indexing API verlangt Owner-Rechte!
   "Add" klicken.

### 2c. Credentials auf Server deployen

JSON-Key per FTP (oder SSH/scp) auf den Server kopieren nach:

```
storage/app/google-indexing-credentials.json
```

Berechtigungen prüfen (lesbar für PHP-FPM-User, nicht world-readable):

```
chmod 640 storage/app/google-indexing-credentials.json
```

In `.env` (Production):

```
GOOGLE_INDEXING_API_ENABLED=true
GOOGLE_INDEXING_API_CREDENTIALS=storage/app/google-indexing-credentials.json
GOOGLE_INDEXING_API_DAILY_QUOTA=180
```

Ist das aktiv, pingt der "Google reindex"-Button im SEO-Editor mit einem
Klick alle Sprach-URLs der aktuellen Seite an Google. Toast-Feedback zeigt
`N/M URL(s) gepingt` (M = Anzahl Sprachen).

> **Quota:** Google erlaubt 200 publish-Requests pro Tag pro Projekt.
> `GOOGLE_INDEXING_API_DAILY_QUOTA=180` lässt 20 Requests Sicherheitspuffer.
> Bei Überschreitung wird übersprungen und geloggt — keine Fehler im Save-Flow.

### 2d. Verifizieren

Nach Cache-Reload (`php artisan config:clear`) eine Page im SEO-Editor
speichern → Logs prüfen:

```
tail -f storage/logs/laravel.log | grep -i google
```

Erwartete Meldung bei Erfolg: keine — der Service loggt nur Fehler/Warnings.
Bei `Token exchange failed` oder `Publish non-success`: Berechtigung in
Search Console prüfen, JSON-Pfad und Inhalt verifizieren.

---

## 3. Workflow nach Content-Änderung

**Automatisch** (kein Klick nötig):
- IndexNow pingt Bing/Yandex
- Google Indexing API pingt Google (wenn aktiviert)
- Sitemap-Cache wird invalidiert
- Live-HTML enthält neuen `Last-Modified`-Header

**Manuell** (für wichtige Änderungen oder wenn die Auto-API-Quota verbraucht ist):
- Im SEO-Editor: ein Klick auf "Google reindex" → alle Sprachen werden via
  Indexing API gepingt → Toast bestätigt.
- Notfall-Fallback: direkt in Search Console → URL Inspection → URL eintragen
  → "Indexierung beantragen".

Effekt-Erwartung: Re-Crawl in Stunden bis 1–2 Tagen statt 1–3 Wochen.
