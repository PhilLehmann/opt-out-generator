<?php

defined('ABSPATH') or die('');
define('OPT_OUT_GENERATOR_PRIVATE_KK', 'Private Krankenversicherungen');

class opt_out_generator_Krankenkassenliste {
	static $instance = null;
	private $data = [];
    private $currentCategory = [];
	
	static function getInstance() {
		return self::$instance;
	}

    function addCategory($name) {
        $this->data[$name] = [];
        $this->currentCategory = $name;
    }
	
	function add($name, $plz, $ort, $strasse, $email, $isPrivate = false) {
		array_push($this->data[$this->currentCategory], new opt_out_generator_Krankenkasse($name, $plz, $ort, $strasse, $email, $isPrivate));
	}
	
    function getNames() {
        $names = [];
		foreach($this->data as $category) {
            foreach($category as $krankenkasse) {
                $names[] = $krankenkasse->name;
            }
		}
        return $names;
    }

	function get($name) {
		foreach($this->data as $category) {
            foreach($category as $krankenkasse) {
                if($krankenkasse->name == $name) {
                    return $krankenkasse;
                }
            }
		}
		return null;
	}
	
	function getFromPost() {
		if(!isset($_POST['gp_kasse'])) {
			wp_die('Parameter "gp_kasse" fehlt.');
		}
		
		$name = $_POST['gp_kasse'];
		foreach($this->data as $category) {
            foreach($category as $krankenkasse) {
                if($krankenkasse->name == $name) {
                    return $krankenkasse;
                }
            }
		}
		
		if(!isset($_POST['gp_kk_name'])) {
			wp_die('Parameter "gp_kk_name" fehlt.');
		}
		if(!isset($_POST['gp_kk_mail']) && (!isset($_POST['gp_kk_plz']) || !isset($_POST['gp_kk_ort']) || !isset($_POST['gp_kk_strasse']))) {
			wp_die('Parameter "gp_kk_mail" und eine komplette Addresse (Parameter "gp_kk_plz", "gp_kk_ort" und "gp_kk_strasse") fehlen.');
		}
		
		// Private Krankenkasse, um den Krankenversichertennummern-Check auszuschalten
		return new opt_out_generator_Krankenkasse($_POST['gp_kk_name'], $_POST['gp_kk_plz'], $_POST['gp_kk_ort'], $_POST['gp_kk_strasse'], $_POST['gp_kk_mail'], true);
	}
	
	function printOptions($includePrivate = false, $selectedNames = []) {
		foreach($this->data as $category => $krankenkassen) {
            if(!$includePrivate && $category == OPT_OUT_GENERATOR_PRIVATE_KK) {
                continue;
            }
            echo '<optgroup label="' . $category . '">';
            foreach($krankenkassen as $krankenkasse) {
                echo '<option value="' . esc_attr($krankenkasse->name) . '" class="' . (in_array($krankenkasse->name, $selectedNames) ? 'selected' : '') . '">' . esc_html($krankenkasse->name) . '</option>';
            }
            echo '</optgroup>';
		}
	}
}

class opt_out_generator_Krankenkasse {
    public $name;
    public $plz;
    public $ort;
    public $strasse;
    public $email;
    public $isPrivate; // if true, disables number check
   
    function __construct($name, $plz, $ort, $strasse, $email, $isPrivate) {
		$this->name = $name;
		$this->plz = $plz;
		$this->ort = $ort;
		$this->strasse = $strasse;
		$this->email = $email;
		$this->isPrivate = $isPrivate;
    }
	
	function canSendLetter() {
		return !empty($this->plz) && !empty($this->ort);
	}
	
	function canSendMail() {
		return !empty($this->email);
	}
}

$opt_out_generator_krankenkassen = new opt_out_generator_Krankenkassenliste();

$opt_out_generator_krankenkassen->addCategory('Mitgliederstärkste Krankenkassen');

$opt_out_generator_krankenkassen->add('BARMER', '10969', 'Berlin', 'Axel-Springer-Straße 44', 'service@barmer.de');
$opt_out_generator_krankenkassen->add('DAK Gesundheit', '20097', 'Hamburg', 'Nagelsweg 27-31', 'service@dak.de');
$opt_out_generator_krankenkassen->add('Techniker Krankenkasse (TK)', '22305', 'Hamburg', 'Bramfelder Straße 140', 'service@tk.de');
$opt_out_generator_krankenkassen->add('HEK - Hanseatische Krankenkasse', '22041', 'Hamburg', 'Wandsbeker Zollstraße 86-90', 'kontakt@hek.de');
$opt_out_generator_krankenkassen->add('hkk Krankenkasse', '28195', 'Bremen', 'Martinistraße 26', 'info@hkk.de');
$opt_out_generator_krankenkassen->add('KKH Kaufmännische Krankenkasse', '30625', 'Hannover', 'Karl-Wiechert-Allee 61', 'service@kkh.de');
$opt_out_generator_krankenkassen->add('KNAPPSCHAFT', '44789', 'Bochum', 'Pieperstraße 14-28', 'krankenversicherung@knappschaft.de');

$opt_out_generator_krankenkassen->addCategory('Weitere gesetzliche Krankenkassen');

$opt_out_generator_krankenkassen->add('AOK Baden-Württemberg', '70191', 'Stuttgart', 'Presselstr. 19', 'info@bw.aok.de');
$opt_out_generator_krankenkassen->add('AOK Bayern', '81739', 'München', 'Carl-Wery-Straße 28', 'infoprivatkunden@service.by.aok.de');
$opt_out_generator_krankenkassen->add('AOK Bremen/Bremerhaven', '28195', 'Bremen', 'Bürgermeister-Smidt-Straße 95', 'info@hb.aok.de');
$opt_out_generator_krankenkassen->add('AOK Hessen', '61352', 'Bad Homburg', 'Basler Straße 2', 'service@he.aok.de');
$opt_out_generator_krankenkassen->add('AOK Niedersachsen', '30519', 'Hannover', 'Hildesheimer Straße 273', 'AOK.Service@nds.aok.de');
$opt_out_generator_krankenkassen->add('AOK Nordost', '14467', 'Potsdam', 'Brandenburger Straße 72', 'service@nordost.aok.de');
$opt_out_generator_krankenkassen->add('AOK Nordwest', '44269', 'Dortmund', 'Kopenhagener Straße 1', 'kontakt@nw.aok.de');
$opt_out_generator_krankenkassen->add('AOK PLUS', '01067', 'Dresden', 'Sternplatz 7', 'service@plus.aok.de');
$opt_out_generator_krankenkassen->add('AOK Rheinland-Pfalz/Saarland', '67304', 'Eisenberg', 'Virchowstraße 30', 'service@rps.aok.de');
$opt_out_generator_krankenkassen->add('AOK Rheinland/Hamburg', '40213', 'Düsseldorf', 'Kasernenstraße 61', 'aok@rh.aok.de');
$opt_out_generator_krankenkassen->add('AOK Sachsen-Anhalt', '39106', 'Magdeburg', 'Lüneburger Straße 4', 'service@san.aok.de');
$opt_out_generator_krankenkassen->add('Audi BKK', '85053', 'Ingolstadt', 'Ferdinand-Braun-Straße 6', 'info@audibkk.de');
$opt_out_generator_krankenkassen->add('BAHN-BKK', '60486', 'Frankfurt', 'Franklinstrasse 54', 'service@bahn-bkk.de');
$opt_out_generator_krankenkassen->add('BERGISCHE KRANKENKASSE', '42719', 'Solingen', 'Heresbachstr. 29', 'info@bergische-krankenkasse.de');
$opt_out_generator_krankenkassen->add('Bertelsmann BKK', '33311', 'Gütersloh', 'Carl-Miele-Str. 214', 'service@bertelsmann-bkk.de');
$opt_out_generator_krankenkassen->add('BIG direkt gesund', '44137', 'Dortmund', 'Rheinische Straße 1', 'info@big-direkt.de');
$opt_out_generator_krankenkassen->add('Betriebskrankenkasse PricewaterhouseCoopers', '34212', 'Melsungen', 'Burgstr. 1-3', 'info@bkk-pwc.de');
$opt_out_generator_krankenkassen->add('BKK Akzo Nobel Bayern', '63906', 'Erlenbach am Main', 'Glanzstoffstraße 1', 'info@bkk-akzo.de');
$opt_out_generator_krankenkassen->add('BKK B. Braun | Aesculap', '34212', 'Melsungen', 'Grüne Straße 1', 'info@bkk-bba.de');
$opt_out_generator_krankenkassen->add('BKK Deutsche Bank AG', '40212', 'Düsseldorf', 'Königsallee 60c', 'bkk.info@bkkdb.de');
$opt_out_generator_krankenkassen->add('BKK Diakonie', '33617', 'Bielefeld', 'Königsweg 8', 'info@bkk-diakonie.de');
$opt_out_generator_krankenkassen->add('BKK EUREGIO', '52525', 'Heinsberg', 'Boos-Fremery-Straße 66', 'info@bkk-euregio.de');
$opt_out_generator_krankenkassen->add('BKK EVM', '56068', 'Koblenz', 'Schützenstr 80 - 82', 'info@bkk-evm.de');
$opt_out_generator_krankenkassen->add('BKK EWE', '26122', 'Oldenburg', 'Staulinie 16-17', 'info@bkk-ewe.de');
$opt_out_generator_krankenkassen->add('BKK exklusiv', '31275', 'Lehrte', 'Zum Blauen See 7', 'info@bkkexklusiv.de');
$opt_out_generator_krankenkassen->add('BKK Faber-Castell & Partner', '94209', 'Regen', 'Bahnhofstraße 45', 'regen@bkk-faber-castell.de');
$opt_out_generator_krankenkassen->add('BKK firmus', '28237', 'Bremen', 'Gottlieb-Daimler Str. 11', 'info@bkk-firmus.de');
$opt_out_generator_krankenkassen->add('BKK Freudenberg', '69469', 'Weinheim', 'Höhnerweg 2 - 4', 'bkk@bkk-freudenberg.de');
$opt_out_generator_krankenkassen->add('BKK GILDEMEISTER SEIDENSTICKER', '33649', 'Bielefeld', 'Winterstr. 49', 'info@bkkgs.de');
$opt_out_generator_krankenkassen->add('BKK Groz-Beckert', '72458', 'Albstadt', 'Unter dem Malesfelsen 72', 'info@bkk-gb.de');
$opt_out_generator_krankenkassen->add('BKK Herkules', '34117', 'Kassel', 'Jordanstr. 6', 'info@bkk-herkules.de');
$opt_out_generator_krankenkassen->add('BKK Linde', '65187', 'Wiesbaden', 'Konrad-Adenauer-Ring 33', 'info@bkk-linde.de');
$opt_out_generator_krankenkassen->add('BKK MAHLE', '70376', 'Stuttgart', 'Pragstr. 26-46', 'info@bkk-mahle.de');
$opt_out_generator_krankenkassen->add('BKK Mobil', '80639', 'München', 'Friedenheimer Brücke 29', 'info@service.mobil-krankenkasse.de');
$opt_out_generator_krankenkassen->add('BKK melitta hmr', '32425', 'Minden', 'Marienstr. 122', 'info@bkk-melitta.de');
$opt_out_generator_krankenkassen->add('BKK Miele', '33332', 'Gütersloh', 'Carl-Miele-Straße 29', 'info@bkk-miele.de');
$opt_out_generator_krankenkassen->add('BKK mkk - meine krankenkasse', '10969', 'Berlin', 'Lindenstraße 67', 'info@meine-krankenkasse.de');
$opt_out_generator_krankenkassen->add('BKK MTU', '88045', 'Friedrichshafen', 'Hochstraße 40', 'info@bkk-mtu.de');
$opt_out_generator_krankenkassen->add('BKK PFAFF', '67655', 'Kaiserslautern', 'Pirmasenser Str. 132', 'info@bkk-pfaff.de');
$opt_out_generator_krankenkassen->add('BKK Pfalz', '67059', 'Ludwigshafen', 'Lichtenberger Str. 16', 'info@bkkpfalz.de');
$opt_out_generator_krankenkassen->add('BKK ProVita', '85232', 'Bergkirchen', 'Münchner Weg 5', 'info@bkk-provita.de');
$opt_out_generator_krankenkassen->add('BKK Public', '38226', 'Salzgitter', 'Thiestrasse 15', 'service@bkk-public.de');
$opt_out_generator_krankenkassen->add('BKK Rieker.RICOSTA.Weisser', '78532', 'Tuttlingen', 'Gänsäcker 3', 'info@bkk-rrw.de');
$opt_out_generator_krankenkassen->add('BKK Salzgitter', '38226', 'Salzgitter', 'Thiestrasse 15', 'service@bkk-salzgitter.de');
$opt_out_generator_krankenkassen->add('BKK Scheufelen', '73230', 'Kirchheim/Teck', 'Schöllkopfstr. 65', 'info@bkk-scheufelen.de');
$opt_out_generator_krankenkassen->add('BKK Schwarzwald-Baar-Heuberg', '78647', 'Trossingen', 'Löhrstraße 45', 'info@bkk-sbh.de');
$opt_out_generator_krankenkassen->add('BKK Technoform', '37079', 'Göttingen', 'August-Spindler-Straße 1', 'willkommen@bkk-technoform.de');
$opt_out_generator_krankenkassen->add('BKK Textilgruppe Hof', '95028', 'Hof', 'Fabrikzeile 21', 'info@BKK-Textilgruppe-Hof.de');
$opt_out_generator_krankenkassen->add('BKK VDN', '58239', 'Schwerte', 'Rosenweg 15', 'info@bkk-vdn.de');
$opt_out_generator_krankenkassen->add('BKK VerbundPlus', '88400', 'Biberach', 'Zeppelinring 13', 'info@bkkvp.de');
$opt_out_generator_krankenkassen->add('BKK Voralb HELLER*INDEX*LEUZE', '72622', 'Nürtingen', 'Gebrüder-Heller-Straße 15', 'info@bkk-voralb.de');
$opt_out_generator_krankenkassen->add('BKK Werra-Meissner', '37269', 'Eschwege', 'Straßburger Str. 5', 'info@bkk-wm.de');
$opt_out_generator_krankenkassen->add('BKK Wirtschaft & Finanzen', '34212', 'Melsungen', 'Bahnhofstr. 19', 'info@bkk-wf.de');
$opt_out_generator_krankenkassen->add('BKK Würth', '74653', 'Künzelsau', 'Gartenstraße 11', 'info@bkk-wuerth.de');
$opt_out_generator_krankenkassen->add('BKK ZF & Partner', '88046', 'Friedrichshafen', 'Otto-Lilienthal-Straße 10', 'friedrichshafen@bkk-zf-partner.de');
$opt_out_generator_krankenkassen->add('BKK_DürkoppAdler', '33605', 'Bielefeld', 'Sieghorster Str. 66', 'info@bkk-da.de');
$opt_out_generator_krankenkassen->add('BKK24', '31683', 'Obernkirchen', 'Sülbecker Brand 1', 'info@bkk24.de');
$opt_out_generator_krankenkassen->add('BMW BKK', '84130', 'Dingolfing', 'Mengkofener Str.  6', 'Informationen@bmwbkk.de');
$opt_out_generator_krankenkassen->add('Bosch BKK', '70469', 'Stuttgart', 'Kruppstr. 19', 'info@Bosch-BKK.de');
$opt_out_generator_krankenkassen->add('Continentale Betriebskrankenkasse', '22335', 'Hamburg', 'Sengelmannstrasse 120', 'kundenservice@continentale-bkk.de');
$opt_out_generator_krankenkassen->add('Debeka BKK', '56072', 'Koblenz', 'Im Metternicher Feld 40', 'info@debeka-bkk.de');
$opt_out_generator_krankenkassen->add('energie-Betriebskrankenkasse', '30659', 'Hannover', 'Oldenburger Allee 24', 'info@energie-bkk.de');
$opt_out_generator_krankenkassen->add('Ernst & Young BKK', '34212', 'Melsungen', 'Rotenburger Str. 16', 'info@ey-bkk.de');
$opt_out_generator_krankenkassen->add('Heimat Krankenkasse', '33602', 'Bielefeld', 'Herforder Str. 23', 'info@heimat-krankenkasse.de');
$opt_out_generator_krankenkassen->add('IKK Brandenburg und Berlin', '14480', 'Potsdam', 'Ziolkowskistr. 6', 'service@ikkbb.de');
$opt_out_generator_krankenkassen->add('IKK classic', '01099', 'Dresden', 'Tannenstr. 4b', 'info@ikk-classic.de');
$opt_out_generator_krankenkassen->add('IKK gesund plus', '39124', 'Magdeburg', 'Umfassungsstraße 85', 'info@ikk-gesundplus.de');
$opt_out_generator_krankenkassen->add('IKK Südwest', '66113', 'Saarbrücken', 'Europaallee 3 - 4', 'info@ikk-sw.de');
$opt_out_generator_krankenkassen->add('IKK - Die Innovationskasse', '23558', 'Lübeck', 'Lachswehrallee 1', 'mail@die-ik.de');
$opt_out_generator_krankenkassen->add('KARL MAYER BKK', '63179', 'Obertshausen', 'Industriestraße 3', 'info@karlmayer-bkk.de');
$opt_out_generator_krankenkassen->add('Koenig & Bauer BKK', '97080', 'Würzburg', 'Friedrich-Koenig-Str. 4', 'info@koenig-bauer-bkk.de');
$opt_out_generator_krankenkassen->add('Krones BKK', '93073', 'Neutraubling', 'Bayerwaldstr. 2L', 'bkk.info@krones.com');
$opt_out_generator_krankenkassen->add('Mercedes-Benz BKK', '28178', 'Bremen', '', 'nord@mercedes-benz-bkk.com'); 
$opt_out_generator_krankenkassen->add('Merck BKK', '64293', 'Darmstadt', 'Frankfurter Straße 129', 'bkk@merckgroup.com');
$opt_out_generator_krankenkassen->add('mhplus Betriebskrankenkasse', '71636', 'Ludwigsburg', 'Franckstraße 8', 'info@mhplus.de');
$opt_out_generator_krankenkassen->add('Novitas BKK', '47059', 'Duisburg', 'Schifferstraße 92-100', 'info@novitas-bkk.de');
$opt_out_generator_krankenkassen->add('pronova BKK', '67061', 'Ludwigshafen', 'Rheinallee 13', 'service@pronovabkk.de');
$opt_out_generator_krankenkassen->add('R+V Betriebskrankenkasse', '65205', 'Wiesbaden', 'Kreuzberger Ring 21', 'info@ruv-bkk.de');
$opt_out_generator_krankenkassen->add('Salus BKK', '63263', 'Neu-Isenburg', 'Siemensstraße 5a', 'service@salus-bkk.de');
$opt_out_generator_krankenkassen->add('SBK', '80339', 'München', 'Heimeranstr. 31', 'info@sbk.org');
$opt_out_generator_krankenkassen->add('SECURVITA BKK', '20099', 'Hamburg', 'Lübeckertordamm 1-3', 'mail@securvita-bkk.de');
$opt_out_generator_krankenkassen->add('SKD BKK', '97421', 'Schweinfurt', 'Schultesstraße 19a', 'service@skd-bkk.de');
$opt_out_generator_krankenkassen->add('Südzucker BKK', '68167', 'Mannheim', 'Joseph-Meyer-Str. 13-15', 'info@suedzucker-bkk.de');
$opt_out_generator_krankenkassen->add('SVLFG', '34131', 'Kassel', 'Weißensteinstr. 70 - 72', 'poststelle@svlfg.de');
$opt_out_generator_krankenkassen->add('TUI BKK', '30625', 'Hannover', 'Karl-Wiechert-Allee 23', 'service@tui-bkk.de');
$opt_out_generator_krankenkassen->add('VIACTIV Krankenkasse', '44803', 'Bochum', 'Suttner-Nobel-Allee 3–5', 'service@viactiv.de');
$opt_out_generator_krankenkassen->add('vivida bkk',  '78056', 'Villingen-Schwenningen', 'Spittelstr. 50',  'info@vividabkk.de');
$opt_out_generator_krankenkassen->add('WMF Betriebskrankenkasse', '73312', 'Geislingen', 'Fabrikstraße 48', 'service@wmf-bkk.de');

$opt_out_generator_krankenkassen->addCategory(OPT_OUT_GENERATOR_PRIVATE_KK);

$opt_out_generator_krankenkassen->add('Allianz Private Krankenversicherungs-Aktiengesellschaft', '85774', 'Unterföhring', 'Dieselstraße 6-8', 'info@allianz.de', true);
$opt_out_generator_krankenkassen->add('ALTE OLDENBURGER Krankenversicherung AG', '49377', 'Vechta', 'Alte-Oldenburger-Platz 1', 'info@alte-oldenburger.de', true);
$opt_out_generator_krankenkassen->add('ALTE OLDENBURGER Krankenversicherung von 1927 Versicherungsverein auf Gegenseitigkeit', '49377', 'Vechta', 'Alte-Oldenburger-Platz 1', 'info@alte-oldenburger.de', true);
$opt_out_generator_krankenkassen->add('ARAG Krankenversicherungs-Aktiengesellschaft', '81829', 'München', 'Hollerithstraße 11', 'msc@arag.de', true);
$opt_out_generator_krankenkassen->add('Augenoptiker Ausgleichskasse VVaG (AKA, true)', '44225', 'Dortmund', 'Generationenweg 4', '', true);
$opt_out_generator_krankenkassen->add('AXA Krankenversicherung Aktiengesellschaft', '51067', 'Köln', 'Colonia Allee 10-20', 'info@axa.de', true);
$opt_out_generator_krankenkassen->add('Barmenia Krankenversicherung AG', '42119', 'Wuppertal', 'Barmenia-Allee 1', '', true);
$opt_out_generator_krankenkassen->add('Bayerische Beamtenkrankenkasse Aktiengesellschaft', '80538', 'München', 'Maximilianstraße 53', '', true);
$opt_out_generator_krankenkassen->add('Central Krankenversicherung Aktiengesellschaft', '50670', 'Köln', 'Hansaring 40 - 50', 'info@central.de', true);
$opt_out_generator_krankenkassen->add('Concordia Krankenversicherungs-Aktiengesellschaft', '30625', 'Hannover', 'Karl-Wiechert-Allee 55', '', true);
$opt_out_generator_krankenkassen->add('Continentale Krankenversicherung a.G.', '44139', 'Dortmund', 'Ruhrallee 92', '', true);
$opt_out_generator_krankenkassen->add('Debeka Krankenversicherungsverein auf Gegenseitigkeit Sitz Koblenz am Rhein', '56073', 'Koblenz am Rhein', 'Ferdinand-Sauerbruch-Straße 18', 'kundenservice@debeka.de', true);
$opt_out_generator_krankenkassen->add('DEVK Krankenversicherungs-Aktiengesellschaft', '50735', 'Köln', 'Riehler Straße 190', '', true);
$opt_out_generator_krankenkassen->add('DKV Deutsche Krankenversicherung Aktiengesellschaft', '50933', 'Köln', 'Aachener Straße 300', 'service@dkv.com', true);
$opt_out_generator_krankenkassen->add('ENVIVAS Krankenversicherung Aktiengesellschaft', '50670', 'Köln', 'Gereonswall 68', '', true);
$opt_out_generator_krankenkassen->add('ERGO Krankenversicherung AG', '90762', 'Fürth', 'Bay', 'Nürnberger Straße 91-95', 'info@ergodirekt.de', true);
$opt_out_generator_krankenkassen->add('Freie Arzt- und Medizinkasse der Angehörigen der Berufsfeuerwehr und der Polizei VVaG', '60327', 'Frankfurt am Main', 'Hansaallee 154', '', true);
$opt_out_generator_krankenkassen->add('Gothaer Krankenversicherung Aktiengesellschaft', '50969', 'Köln', 'Arnoldiplatz 1', '', true);
$opt_out_generator_krankenkassen->add('HALLESCHE Krankenversicherung auf Gegenseitigkeit', '70178', 'Stuttgart', 'Reinsburgstraße 10', '', true);
$opt_out_generator_krankenkassen->add('HanseMerkur Krankenversicherung AG', '20354', 'Hamburg', 'Siegfried-Wedells-Platz 1', 'info@hansemerkur.de', true);
$opt_out_generator_krankenkassen->add('HanseMerkur Krankenversicherung auf Gegenseitigkeit', '20354', 'Hamburg', 'Siegfried-Wedells-Platz 1', 'info@hansemerkur.de', true);
$opt_out_generator_krankenkassen->add('HanseMerkur Speziale Krankenversicherung AG', '20354', 'Hamburg', 'Siegfried-Wedells-Platz 1', 'info@hansemerkur.de', true);
$opt_out_generator_krankenkassen->add('HUK-COBURG-Krankenversicherung AG', '96450', 'Coburg', 'Bahnhofsplatz', 'Info@HUK-COBURG.de', true);
$opt_out_generator_krankenkassen->add('INTER Krankenversicherung AG', '68165', 'Mannheim', 'Erzbergerstraße 9-15', '', true);
$opt_out_generator_krankenkassen->add('Krankenunterstützungskasse der Berufsfeuerwehr Hannover', '30625', 'Hannover', 'Karl-Wiechert-Allee 60 B', '', true);
$opt_out_generator_krankenkassen->add('Landeskrankenhilfe V.V.a.G.', '21335', 'Lüneburg', 'Uelzener Straße 120', '', true);
$opt_out_generator_krankenkassen->add('LIGA Krankenversicherung katholischer Priester Versicherungsverein auf Gegenseitigkeit Regensburg', '93055', 'Regensburg', 'Weißenburgstraße 17', '', true);
$opt_out_generator_krankenkassen->add('Lohnfortzahlungskasse Aurich VVaG', '26603', 'Aurich', 'Lambertistraße 16', '', true);
$opt_out_generator_krankenkassen->add('Lohnfortzahlungskasse Leer VVaG', '26789', 'Leer', 'Grosser Stein 5', 'i. Hs. Huneke GmbH', '', true);
$opt_out_generator_krankenkassen->add('LVM Krankenversicherungs-AG', '48151', 'Münster', 'Kolde-Ring 21', 'info@lvm.de', true);
$opt_out_generator_krankenkassen->add('Mecklenburgische Krankenversicherungs-Aktiengesellschaft', '30625', 'Hannover', 'Platz der Mecklenburgischen 1', '', true);
$opt_out_generator_krankenkassen->add('MÜNCHENER VEREIN Krankenversicherung a.G.', '80336', 'München', 'Pettenkoferstraße 19', '', true);
$opt_out_generator_krankenkassen->add('NÜRNBERGER Krankenversicherung Aktiengesellschaft', '90482', 'Nürnberg', 'Ostendstraße 100', '', true);
$opt_out_generator_krankenkassen->add('ottonova Krankenversicherung AG', '80333', 'München', 'Ottostraße 4', 'helpdesk@ottonova.de', true);
$opt_out_generator_krankenkassen->add('praenatura Versicherungsverein auf Gegenseitigkeit (VVaG, true)', '65428', 'Rüsselsheim', 'Bahnhofsplatz 1', '', true);
$opt_out_generator_krankenkassen->add('Provinzial Krankenversicherung Hannover AG', '30159', 'Hannover', 'Schiffgraben 4', '', true);
$opt_out_generator_krankenkassen->add('R+V Krankenversicherung Aktiengesellschaft', '65189', 'Wiesbaden', 'Raiffeisenplatz 1', 'ruv@ruv.de', true);
$opt_out_generator_krankenkassen->add('SIGNAL IDUNA Krankenversicherung a.G.', '44139', 'Dortmund', 'Joseph-Scherer-Straße 3', '', true);
$opt_out_generator_krankenkassen->add('SONO Krankenversicherung a.G.', '46242', 'Bottrop', 'Westring 73', '', true);
$opt_out_generator_krankenkassen->add('St. Martinus Priesterverein der Diözese Rottenburg-Stuttgart- Kranken- und Sterbekasse (KSK) - Versicherungsverein auf Gegenseitigkeit (VVaG)', '70178', 'Stuttgart', 'Hohenzollernstraße 23', '', true);
$opt_out_generator_krankenkassen->add('Süddeutsche Krankenversicherung a.G.', '70736', 'Fellbach', 'Raiffeisenplatz 5', '', true);
$opt_out_generator_krankenkassen->add('UNION KRANKENVERSICHERUNG AKTIENGESELLSCHAFT', '66123', 'Saarbrücken', 'Peter-Zimmer-Straße 2', '', true);
$opt_out_generator_krankenkassen->add('uniVersa Krankenversicherung a.G.', '90489', 'Nürnberg', 'Sulzbacher Straße 1-7', '', true);
$opt_out_generator_krankenkassen->add('Versicherer im Raum der Kirchen Krankenversicherung AG', '32756', 'Detmold', 'Doktorweg 2-4', '', true);
$opt_out_generator_krankenkassen->add('vigo Krankenversicherung VVaG', '40210', 'Düsseldorf', 'Konrad-Adenauer-Platz 12', '', true);
$opt_out_generator_krankenkassen->add('Württembergische Krankenversicherung Aktiengesellschaft', '70176', 'Stuttgart', 'Gutenbergstraße 30', '', true);

opt_out_generator_Krankenkassenliste::$instance = $opt_out_generator_krankenkassen;
