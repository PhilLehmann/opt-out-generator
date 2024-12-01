<?php

defined('ABSPATH') or die('');

function opt_out_generator_pdf() {

    define('OPT_OUT_GENERATOR_PROCESS_ID', opt_out_generator_get_process_id($_POST));
    if(OPT_OUT_GENERATOR_PROCESS_ID == '') {
        die();
    }
    
    $processes = opt_out_generator_get_processes();
    $process = $processes[OPT_OUT_GENERATOR_PROCESS_ID];

	require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	
	$subject = esc_html($process['mail_subject']);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('AK Vorratsdatenspeicherung');
    $pdf->SetTitle($subject);
    $pdf->SetSubject($subject);
    
	$pdf->SetMargins(22, 27, 20);
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->AddPage();

	require_once __DIR__ . '/krankenkassen.php';
	$opt_out_generator_krankenkassen = opt_out_generator_Krankenkassenliste::getInstance();
	$krankenkasse = $opt_out_generator_krankenkassen->getFromPost();
	
	if(!isset($_POST['gp_name']) || !isset($_POST['gp_strasse']) || !isset($_POST['gp_plz']) || !isset($_POST['gp_ort'])) {
		wp_die('Einer der Parameter "gp_name", "gp_strasse", "gp_plz", oder "gp_ort" fehlt.');
	}
	
    $gp_name = esc_html($_POST['gp_name']);
    $gp_strasse = esc_html($_POST['gp_strasse']);
    $gp_plz = esc_html($_POST['gp_plz']);
    $gp_ort = esc_html($_POST['gp_ort']);

    $html = <<<TXT
    <div style="text-align: right;">
        <b>{$gp_name}</b><br/>
        {$gp_strasse}<br/>
        {$gp_plz} {$gp_ort}
    </div>
	<br/>
	<br/>
	<br/>
	<font size="8">{$gp_name} - {$gp_strasse} - {$gp_plz} {$gp_ort}<br/></font><br/>
	<b>{$krankenkasse->name}</b><br/>
	{$krankenkasse->strasse}<br/>
	{$krankenkasse->plz} {$krankenkasse->ort}
	<br/>
	<br/>
	<br/>
	<br/>
	<br/>
	<b>{$subject}</b>
	<br/>
	<br/>
TXT;
	
	$html .= wpautop(opt_out_generator_get_mail_text_from_post());

    $pdf->writeHTML($html, true, false, true, false, '');
    
	$lineStyle = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
	$pdf->Line(7, 105, 11, 105, $lineStyle);
	$pdf->Line(7, 210, 11, 210, $lineStyle);
	
    $pdf->Output($subject . '.pdf', 'I');
}