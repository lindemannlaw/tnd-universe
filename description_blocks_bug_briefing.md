# Bug-Report: `description_blocks` Persistence Bug

## Symptom

Im Admin-Panel einer Laravel 12 Anwendung (Spatie `laravel-translatable` v6.12.0) können Felder innerhalb von `description_blocks` (z.B. `col_span`, `col_start`, WYSIWYG-Textinhalte) nach dem initialen Erstellen eines Projekteintrags nicht mehr dauerhaft geändert werden. Der Save-Vorgang zeigt eine Erfolgsmeldung, aber beim erneuten Öffnen des Modals sind die alten Werte wieder da. Das initiale Erstellen (`store`) funktioniert korrekt.

## Betroffene Felder

- `description_blocks` — ein JSON-Feld in der `projects`-Tabelle, das ein mehrstufig verschachteltes Array von Inhaltsblöcken speichert (Typen: `text`, `text_column_row`, `floating_gallery`), jeweils pro Sprache (`en`, `de`).
- Das Feld ist sowohl in `$translatable` (Spatie HasTranslations) als auch in `$fillable` und war **zusätzlich** in `casts()` als `'json'` deklariert.

## Technischer Stack

- Laravel 12.40.2, PHP 8.3
- Spatie `laravel-translatable` v6.12.0 — Trait `HasTranslations` auf dem Model
- MySQL mit JSON-Spalte `description_blocks` (nullable, via Migration)
- Blade + Vanilla JS Frontend mit AJAX-Formsubmission (Axios, `multipart/form-data`)
- Modal-basiertes Editing: Nach Save wird das Modal per fetch-Request zum `edit()`-Endpoint refreshed
- Hosting: Plesk, Deploy via Plesk Git-Integration

## Architektur des Datenflusses

1. **Client**: User ändert Werte in Formularfeldern (z.B. `description_blocks[en][0][items][0][col_span]`). JS erstellt FormData aus dem `<form>`, sendet AJAX POST (PATCH).
2. **Server — Validation**: `UpdateRequest` validiert `description_blocks` als `required|array` mit tief verschachtelten Regeln inkl. `col_span` (`nullable|integer|min:1|max:12`).
3. **Server — Preparation**: `ProjectController::prepareDescriptionBlocksData()` transformiert die validated data in die finale Struktur. EN ist "source of truth" für Layout-Felder; DE wird strukturell an EN angeglichen, behält aber eigene Text-Felder.
4. **Server — Save**: `$project->fill($data)` → Spatie `setAttribute` → `setTranslations()` → `json_encode()` → `$this->attributes['description_blocks']`. Dann `$project->save()`.
5. **Server — Response**: JSON mit Erfolgsmeldung. Client macht dann separaten GET-Request zum `edit()`-Endpoint für Modal-Refresh (Route-Model-Binding lädt Project frisch aus DB).

## Relevante Dateien

- `app/Models/Project.php`: Model mit `$fillable`, `$translatable`, `casts()`. `description_blocks` war in allen drei.
- `app/Http/Controllers/Admin/Portfolio/ProjectController.php`: `store()`, `update()`, `edit()`, `prepareDescriptionBlocksData()`.
- `app/Http/Requests/Admin/Portfolio/Project/UpdateRequest.php`: Validierungsregeln.
- `app/Traits/HasImageProcessing.php`: `saving`-Event auf `description`-Attribut + `processImagesInDescription()`.
- `resources/js/admin/components/projectDescriptionBlocks.js`: Client-seitige Block-Verwaltung, `syncBuildersOnInit` synchronisiert EN→DE Layout-Felder.
- `resources/js/admin/components/ajaxWithUpdateFromView.js`: AJAX-Submit + Modal-Refresh-Logik.
- `resources/js/ajax.js`: FormData-Erstellung und Axios-Call.
- `vendor/spatie/laravel-translatable/src/HasTranslations.php`: Spatie Trait v6.

## Bisherige Diagnose-Erkenntnisse

1. **Client-seitige Logs bestätigen**: Die korrekten Werte (z.B. `col_span=6`) werden im FormData gesendet, kommen im Server-Response-HTML zurück, und sind nach Modal-Refresh im DOM vorhanden.

2. **Server-seitige Logs waren veraltet**: Der User lieferte Logs vom 17. März, die noch den alten `SQLSTATE[42S22]: Column not found: 'description_blocks'`-Fehler zeigten. Dieser Fehler ist mittlerweile behoben (Migration existiert, `post-deploy.php` meldet "Nothing to migrate").

3. **Doppelter JSON-Cast**: Das Model hatte `'description_blocks' => 'json'` in der `casts()` Methode. Spatie's `initializeHasTranslations()` setzt aber bereits `'array'` Cast für alle `$translatable` Attribute via `mergeCasts()`. Die Initialisierungsreihenfolge in Laravel 12:
   - `initializeHasAttributes()` merged `casts()` → setzt `'json'`
   - `initializeHasTranslations()` ruft `mergeCasts()` → überschreibt mit `'array'`

   Effektiv war der Cast `'array'`, aber die `casts()`-Deklaration könnte subtile Konflikte bei `isDirty()`-Vergleichen verursacht haben.

4. **Double-Save Pattern**: Der `update()`-Controller machte zwei `save()`-Aufrufe:
   ```php
   $project->fill($data);
   $project->save();  // Erster Save — alle Felder inkl. description_blocks
   $project->description = $project->processImagesInDescription(...);
   $project->save();  // Zweiter Save — verarbeitet temp Bilder in description
   ```
   Nach dem ersten `save()` wird `syncOriginal()` aufgerufen. Der zweite Save sollte nur `description` betreffen, aber dieses Pattern ist fragil.

5. **Translation-Fehler (HTTP 500)**: Separat gefixt — `DeepLTranslationService` und `SeoGenerationService` crashten mit TypeError wenn API-Keys null waren. Fix: Null-Coalescing in Constructors + Default-Werte in `config/services.php`.

## Bereits durchgeführte Fixes

1. **JSON-Casts entfernt** (`app/Models/Project.php`): Alle `'json'`-Casts für translatable Attribute aus `casts()` entfernt. Nur `'active' => 'boolean'` bleibt. Spatie handhabt das JSON-Encoding automatisch.

2. **Single-Save konsolidiert** (`ProjectController.php`): `processImagesInDescription` wird jetzt VOR dem `save()` aufgerufen, dann nur ein einziger `saveOrFail()`. Der zweite `save()` wurde entfernt.

3. **Raw-DB-Diagnostic hinzugefügt**: Nach `DB::commit()` wird direkt via `DB::table('projects')->where('id', ...)->value('description_blocks')` geloggt, was tatsächlich in der Datenbank steht (bypass Eloquent/Spatie).

4. **Debug-Logging aufgeräumt**: Alte mehrstufige Debug-Logs entfernt, nur noch ein pre-save Log (zeigt `isDirty` und dirty keys) und der Raw-DB-Log nach Commit.

## Aktueller Stand

Der Fix (Commit `be451e6`) wurde auf `main` gepusht und via Plesk deployed. `post-deploy.php` lief erfolgreich (Cache geleert). Der User meldet aber, dass das Problem weiterhin besteht.

## Was noch nicht untersucht wurde

- **Server-seitige Logs nach dem neuesten Deploy**: Wir haben noch KEINEN `laravel.log`-Output mit dem neuen `[debug-fb4a59] RAW DB after commit`-Eintrag gesehen. Dieser würde definitiv zeigen, ob die Daten in die DB geschrieben werden oder nicht.
- **Direkte Datenbank-Inspektion**: Ob die `description_blocks`-Spalte tatsächlich existiert und beschreibbar ist, wurde nie direkt via SQL verifiziert.
- **Ob der Plesk Git-Deploy die PHP-Dateien tatsächlich aktualisiert**: Es gab wiederholt Hinweise, dass der Deploy-Prozess möglicherweise nicht alle Dateien aktualisiert.

## Nächster empfohlener Schritt

Den aktuellen `storage/logs/laravel.log` vom Server holen und nach `[debug-fb4a59] RAW DB after commit` und `[debug-fb4a59] pre-save` suchen. Diese Logs zeigen:
- Ob `description_blocks` als "dirty" erkannt wird
- Was tatsächlich in der Datenbank steht nach dem Commit

Falls diese Logs nicht vorhanden sind, wurde der PHP-Code nicht korrekt deployed.
