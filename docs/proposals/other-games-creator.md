# Voorstel: rework "Create other game server"

Status: **deels uitgevoerd in 0.3.0-beta.20**.
- **Klaar:**
  - fase 1: index en zoeken;
  - fase 2: automatische egg-installatie met de ability *Install new games*;
  - het eerste deel van fase 3: getypeerde instellingen en een Advanced-blok.
- **Nog open:**
  - EULA/licentie vooraf accepteren;
  - aanbevolen resources per game;
  - een eigen index-URL;
  - fase 4: de voortgangskaart.
Onderdeel van de add-on **Easy server creator** (Settings → Easy server creator).

## Probleem nu
- De keuzelijst toont alleen de eggs die al op het panel staan, en alleen hun technische egg-naam ("Valheim (Plus)").
- Een game die er niet is, moet een admin eerst zelf opzoeken, downloaden en importeren in Eggs.
- Na het aanmaken is er weinig feedback. De egg-installatie en de eerste server-installatie lijken te "hangen", en sommige games (bijvoorbeeld via een EULA) vragen nog om een herstart.
- Het formulier "Game settings" toont alle egg-variabelen ongefilterd, met technische namen.

## Doel
Je zoekt een game op naam, kiest hem, vult hooguit een paar duidelijke instellingen in en ziet live hoe ver de server is. Of de egg er al was, maakt voor de gebruiker niet uit.

## Flow (4 stappen, zelfde wizard-stijl als Minecraft)
1. **Game kiezen**
   - Een zoekveld met een grid van gamekaarten: icoon, naam en een label *Installed* of *Available*.
   - Filterchips: *Installed*, *Popular*, *All*.
   - Zoeken matcht op gamenaam, aliassen ("CS2", "Counter-Strike 2") en egg-naam.
   - Heeft één game meerdere eggs (vanilla/modded, Linux/Proton)? Dan volgt een kleine keuze "Variant".
2. **Basis**
   - Naam en eventueel een icoon.
   - Resources als preset (*Klein / Normaal / Groot*), afgeleid van de aanbeveling bij de egg of van de admin-defaults per game. Admins kunnen dit uitklappen naar exacte waarden.
3. **Game settings**
   - Alleen de belangrijke variabelen: wachtwoord, max spelers, wereld/map, versie en dergelijke. Ze krijgen leesbare labels, het juiste veldtype (toggle, select, number) en worden gevalideerd met de egg-rules.
   - Een uitklapbaar paneel "Advanced" bevat de rest.
   - Licenties en EULA's worden **vooraf** geaccepteerd, met een checkbox en een link. Daarna zet Simplify de juiste variabele of het juiste bestand, zodat er geen herstartlus ontstaat.
4. **Overzicht → Create**
   - Een samenvatting.
   - Bij een *Available*-game staat erbij: "De egg wordt eerst geïnstalleerd".

## Egg-index (gamedatabase)
- **Bron:** de officiële Pelican egg-repositories op GitHub (`pelican-eggs/*`: games-steamcmd, games-standalone, minecraft, enzovoort), via een gegenereerde index (JSON). Die index bevat per egg:
  - gamenaam en aliassen
  - categorie
  - download-URL van de egg-JSON
  - de sha256
  - Steam-appid (voor het icoon, met fallback)
  - aanbevolen resources
- **Verversen:** de index wordt **lokaal gecachet** (`storage/app/simplify/egg-index.json`) en hooguit één keer per dag ververst met de bestaande scheduler (`simplify:maintenance`). Zoeken gebeurt dan zonder netwerkverkeer, wat weinig processing kost.
- **Offline of geblokkeerd:** zonder netwerk werkt alleen *Installed*; de rest van de wizard blijft gewoon werken.
- **Eigen index:** een admin kan een eigen index-URL instellen, bijvoorbeeld een fork of een eigen eggs-repo.

## Egg automatisch installeren
- **Rechten:**
  - Admins mogen altijd installeren.
  - Voor andere rollen komt er een nieuwe ability onder Users & access: *"Install new games (eggs)"*, standaard **uit**.
  - Zonder die ability ziet de gebruiker alleen *Installed*-games, plus eventueel de games die een admin op de allowlist "Other games" heeft gezet.
- **Installatie:**
  - Dit hergebruikt `EggInstaller::ensure()`: download, controle van de sha256 tegen de index, import via Pelican's eigen egg-importer, en een lock tegen dubbele imports.
  - Een egg die al bestaat (zelfde UUID of URL) wordt hergebruikt.
- **Na de import:**
  - De egg wordt automatisch toegevoegd aan de toegestane "Other games" als de instelling *"Allow newly installed games for self-service"* aan staat.
  - Een admin krijgt daarvan een melding.

## Voortgang tijdens het aanmaken ("installatie-overhaul")
- Na Create opent direct de serverpagina met een statuskaart die de stappen laat zien:
  *Egg installeren → Server aanmaken → Bestanden downloaden (installatiescript) → Eerste start → Klaar*
- De status komt uit Pelican/Wings (`server.status = installing`, de install-log, de eerste "running"). Een poll volgt de bestaande refresh-instellingen en stopt zodra de server klaar is.
- **Bij een fout** toont de kaart de laatste regels van de install-log, met de knoppen *Opnieuw installeren* en *Naar console*.

## Instellingen (tab Easy server creator)
- **Other games**
  - de toegestane games/eggs (bestaand);
  - de nieuwe toggles "Search the online egg index" en "Allow newly installed games for self-service";
  - een optionele eigen index-URL.
- **Resource-presets per game:** standaard volgen ze de egg-aanbeveling, en per game kun je ze overschrijven.

## Technisch (kort)
- **Nieuwe service** `EggIndex`:
  - `search()` en `find()`;
  - `refresh()` met ETag, zodat het verkeer klein blijft;
  - normaliseren van namen en aliassen.
- **Uitbreidingen:**
  - `OtherGameCatalog` wordt uitgebreid met installed- en available-kaarten en een filter voor de belangrijke variabelen: een heuristiek plus een kleine lijst per bekende game.
  - De creator-stap "Game" wordt een eigen Livewire-gestuurde zoekcomponent: zoeken gebeurt client-side op de gecachte index, zonder roundtrip per toetsaanslag.
- **Tests:**
  - index-parsing;
  - rechten (geen ability → geen download);
  - checksum-mismatch → weigeren;
  - de wizard-flow in de browser-harness.

## Fasering
1. Index en zoeken, plus installed/available in de wizard (alleen voor admins).
2. Automatische egg-installatie met de nieuwe ability, plus meldingen.
3. Gefilterde game settings, plus vooraf accepteren van de EULA of licentie.
4. De voortgangskaart (installatie-overhaul) voor alle creaties, Minecraft inbegrepen.

## Besluiten
- **Nieuwe games installeren:** standaard mogen alleen admins dat. Je kunt het per rol aanzetten met de ability *"Install new games (eggs)"* onder Users & access (standaard uit).
- **Beelden:** alleen iconen, geen Steam-banners.
- **Popular:** gebaseerd op wat er op het eigen panel het meest wordt aangemaakt. Zolang er nog weinig servers zijn, vult een kleine vaste startlijst aan.
