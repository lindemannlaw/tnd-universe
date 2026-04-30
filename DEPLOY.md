# Deploy Guide (Live / Prelive)

Diese Anleitung ist auf den aktuellen Prozess für `tnd_universe` abgestimmt.

## Branches

- `main` = Live
- `develop` = Prelive

## Zielbild

Ein vollständiger Deploy besteht immer aus:

1. Code (Git)
2. Datenbank-Inhalt (DB)
3. Uploads/Media-Dateien (`storage/app/public`)
4. Post-Deploy Schritte (Caches, Symlink, Migrationen)

Nur Code-Deploy reicht nicht für vollständigen Content (insbesondere Bilder).

---

## Standard-Deploy nach Prelive

### 1) Code deployen

- Änderungen nach `develop` pushen.
- Workflow **Deploy to prelive** ausführen (oder Metanet Git Deploy nutzen).

### 2) Datenbank von Live nach Prelive synchronisieren

Option A (empfohlen): phpMyAdmin
- Live DB exportieren (`.sql`)
- Prelive DB importieren (`.sql`)

Option B (CLI, falls verfügbar):

```bash
mysqldump -h LIVE_DB_HOST -P LIVE_DB_PORT -u LIVE_DB_USER -p LIVE_DB_NAME > live.sql
mysql -h PRELIVE_DB_HOST -P PRELIVE_DB_PORT -u PRELIVE_DB_USER -p PRELIVE_DB_NAME < live.sql
```

### 3) Media-Dateien synchronisieren

Von Live nach Prelive kopieren:

- `storage/app/public/`

Wenn SSH verfügbar:

```bash
rsync -avz --delete /path/live/storage/app/public/ /path/prelive/storage/app/public/
```

Ohne SSH: per FTP/SFTP oder File Manager denselben Ordnerinhalt kopieren.

### 4) Post-Deploy ausführen

Auf prelive:

- `post-deploy.php?token=...`

Das sorgt für:
- `php artisan migrate --force`
- `php artisan optimize:clear` / relevante Cache-Clears
- `php artisan storage:link` (Symlink `public/storage`)

---

## Quick Check nach Deploy

1. Home lädt ohne Fehler
2. Bilder auf Home sichtbar
3. Portfolio-Liste lädt
4. Projekt-Detailseite lädt
5. Admin Login und Seitenbearbeitung funktionieren

---

## Häufige Probleme

- **Bilder fehlen**  
  Ursache: DB ist synchron, aber `storage/app/public` nicht.

- **Portfolio/News Pagination Fehler**  
  Der Code enthält jetzt Fallback auf `pagination::default`, falls `vendor.pagination.public` fehlt.

- **`localhost` vs `127.0.0.1` lokal**  
  Auf lokale `.env` Werte achten (`APP_URL`, `SESSION_DOMAIN`, DB Host).

---

## Sicherheits-Hinweise

- Keine Live-Credentials in Repo committen.
- Bei versehentlich exponierten Keys sofort rotieren.
- Prelive sollte eigene `.env` Werte haben (Mail, Queue, Tracking, etc.).
