# RL_Luefter
Beschreibung des Moduls.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

Das Modul dienst zru Steuerung des RL-Lüfter. 
Die Lüfetr werden auch unter Label OXXIFY vertrieben, getestet wurde das Modul bisher nur mit 2 TwinFresh Expert Duo RW-30 V.2.



### 2. Voraussetzungen

- IP-Symcon ab Version 7.1

### 3. Software-Installation

* Über den Module Store das 'RL_Luefter'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen
https://github.com/janpeterdietz/RL-Luefter

### 4. Einrichten der Instanzen in IP-Symcon

Die Lüfter können über die Discovery Instanz automatisch im WLAN gefunden werden.
In diesem Fall wird die Lüfter Indentifikation und IP-Adresse ausgelsen und konfiguiert.

Alternativ Unter 'Instanz hinzufügen' kann das 'RL_Luefter'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                    | Beschreibung
----------------------- | ------------------
Update Interval         | Wert in Sekunden zur Zyklischen Abfrage der Lüfterdaten
Lüfter Identifikation   | Lüfter ID als String
IP-Adresse              | IP Adresse des Lüfters


Es wird immmer das Standardpasswort verwendet „1111“. (nicht konfigierbar)

Das Anlegen einer Lüfter Instanz oder Discovery Instant öffent einen UDP Port auf Port 5000.
Der Port kann derzeit nicht geändert werden.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Funktionsvariablen (lesend und schreibend)

Name          | Typ           | Beschreibung
------------- | ------------- | ------------
State         |    Bolean     | Lüfter Status (Ein / Aus)
Powermode     |    Integer    | Stufe 1 bis Stufe 3, Manuel (255) 
Speed         |    Integer    | 0 bis 255
Operatingmode |    Integer    | 1 = Zuluft, 2 = Wärmetauscher, 3 = Abluft


Werte und Anzeiegevariablen (nur lesend)

Name                        | Typ           
--------------------------- | ------------- 
Luftfeuchte                 |    Integer    
Filterreinigung notwendig   |    Bool
Zeit bis Filterreinigung    |    String
Systemwarnung               |    Integer
RTC Batteriespannugg        |    Integer


#### Profile

Name                | Typ
------------------- | -------
  RLV.Powermode     | Integer => für Powermode
  RLV.Operatingmode | Integer => für Operationg Mode
  RLV.AlertLevel    | Integer => für Systemwarnung
  

### 6. Visualisierung


### 7. PHP-Befehlsreferenz

über 
RequestAction(int $id, $value) können die Werte 
       State
       Powermode
       Speed
und    Operatingmode einzeln gesetzt werden.

RL_SetValueEx(int $id, array)
erlaubt ein setzen von meheren Werten gleichzeitig.
Es müssen nicht alle gestzet werden.
Beispiel:
$new_para['State'] = true; // Lüfter Ein
$new_para['Operatingmode'] = 1; // Wärmerückgewinnung 
$new_para['Powermode'] = 0xff; // Manuel Lüftergeschwindigkeit
$new_para['Speed'] = 10 ; // Lüfter Speed
RL_SetValueEx($id_Lufter_instanz, $new_para);

RL_RequestStatus(int $id) => erlaubt das abfragen der oben genannten Variablen.

RL_ResetFilterClean(int $id) => Rücksetzen des Filtereinigungsstatus 
			