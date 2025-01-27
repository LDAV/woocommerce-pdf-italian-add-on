<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Italian_add_on_Settings_fpa' ) ) {

	class WooCommerce_Italian_add_on_Settings_fpa {
	
		public function __construct() {
			add_action( 'admin_init', array(&$this, 'init_settings' ));
			add_action( 'wcpdf_IT_fpa_settings_output', array( $this, 'output' ) );
		}

		public function output( $section ) {
			wp_enqueue_style('woocommerce_admin_styles', plugins_url() . 'woocommerce/assets/css/admin.css');
			wp_register_script( 'selectWoo', plugins_url() . 'woocommerce/assets/js/selectWoo/selectWoo.full.min.js' );
			wp_enqueue_script( 'selectWoo' );
			wp_register_script( 'wc-enhanced-select', plugins_url() . 'woocommerce/assets/js/admin/wc-enhanced-select.min.js' );
			wp_enqueue_script( 'wc-enhanced-select' );
?>
<div class="wcpdf_it_blocks">
<div class="wcpdf_it_block1">
<?php
			settings_fields( WCPDF_IT()->settings->fpa_settings_key );
			do_settings_sections( WCPDF_IT()->settings->fpa_settings_key );
			submit_button();
?>
<h3>Esporta i dati in formato XML con la versione premium</h3>
<div style="float: left; margin-right: 20px; width:100%; max-width: 240px; "><a href="https://ldav.it/shop/plugin/woocommerce-italian-add-on/"><img src="https://ldav.it/wp-content/uploads/2015/05/banner-772x250-772x240.jpg" width="100%"></a></div>
<p>La <a href="https://ldav.it/shop/plugin/woocommerce-italian-add-on/">versione premium del plugin (WooCommerce Italian add-on)</a>, oltre a consentire la raccolta di tutti i dati necessari, ti permette anche di esportare le fatture nel formato XML previsto dal Sistema di Interscambio dell'Agenzia delle Entrate, quindi in piena conformità con la norma in vigore dal 1 gennaio 2019.</p>
</div>
<div class="wcpdf_it_block2">
	<h2>Servizi di compilazione, trasmissione e conservazione fatture elettroniche</h2>
	<p>Esistono molti servizi che consentono a imprese e professionisti di gestire la fatturazione elettronica. Fra le tante possibilità ne esiste una totalmente gratuita, messa a disposizione dall'Agenzia delle Entrate.</p>
	<p>Il sistema di esportazione che usiamo nella <a href="https://ldav.it/shop/plugin/woocommerce-italian-add-on/">versione premium del plugin (WooCommerce Italian add-on)</a> è stato studiato in funzione di queste applicazioni online e quindi in piena compatibilità con il Sistema di Interscambio, il meccanismo che provvede al controllo e la trasmissione delle fatture.</p>
	<h3><img src="<?php echo esc_attr(WooCommerce_Italian_add_on::$plugin_url) ?>images/agenziaEntrate_logo.png" width="80"> Fatture e Corrispettivi</h3>
	<p><strong>Fatture e corrispettivi</strong> è un’apposita area del sito dell’Agenzia delle entrate, rivolta alle imprese e ai professionisti (soggetti titolari di partita IVA), che offre tutti i servizi per <strong>generare, trasmettere e conservare</strong> (<strong>gratuitamente</strong>) le fatture elettroniche verso clienti privati e PA</p>
	<ul><li><strong><a href="https://www.agenziaentrate.gov.it/wps/content/Nsilib/Nsi/Schede/Comunicazioni/Fatture+e+corrispettivi/Acc+servizio+Fatture+e+corrispettivi/?page=schedecomunicazioni" target="_blank">più informazioni su Fatture e corrispettivi</a></strong></li>
	<li><strong><a href="https://ivaservizi.agenziaentrate.gov.it/portale/" target="_blank">accedi a Fatture e corrispettivi</a></strong></li>
	</ul>
	<h3>La fattura elettronica</h3>
	<p>La principale caratteristica della fattura elettronica è che la sua trasmissione è demandata a un soggetto terzo, il <strong><a href="http://fatturapa.gov.it/export/fatturazione/it/b-1.htm" target="_blank">Sistema di Interscambio (SdI)</a></strong> dell'Agenzia delle Entrate, che provvede a inviare i dati della fattura al destinatario.</p>
	<p>Il destinatario è identificato tramite un codice di 7 caratteri o, più comunemente, dal suo indirizzo di Posta Elettronica Certificata, caratteristica comune obbligatoria per tutti i professionisti e le imprese. Per privati cittadini o residenti all'estero (codice identificativo 0000000), il Sistema di Interscambio funge semplicemente da archivio.</p>
	<h3>La raccolta dei dati obbligatori</h3>
	<p>In caso di acquisto di aziende o professionisti residenti in Italia, WooCommerce Italian Add-on aggiunge al checkout la richiesta (obbligatoria) di un Codice Identificativo o di un indirizzo di Posta Elettronica Certificata, che si aggiungono ai dati di una normale fattura.</p>
<?php if(class_exists( 'WPO_WCPDF' )){?>
	<p>Il numero e la data della fattura, se presenti, vengono ricavati dalle informazioni associate all'acquisto da <a href="/wp-admin/admin.php?page=wpo_wcpdf_options_page">WooCommerce PDF Invoice &amp; Packing Slips</a></p>
<?php } else { ?>
	<div class="avviso">
	<p><strong>ATTENZIONE: Non è stato rilevato un sistema per identificare il numero e la data della fattura.</strong><br>
A ciascun ordine dovranno essere associati due campi (custom fields) denominati in questo modo:</p>
	<ul>
		<li><strong>woo_pdf_invoice_id</strong>: contenente il numero della fattura</li>
		<li><strong>woo_pdf_invoice_date</strong>: contenente la data della fattura in formato aaaa-mm-gg (Y-m-d)</li>
	</ul>
	</div>
<?php } ?>
	<p>I dati da trasmettere sono completati poi dagli <strong>Identificativi fiscali</strong> del fornitore (cedente prestatore) da compilare in questa pagina.</p>
	<h3>Esportazione dei dati delle fatture</h3>
	<p>L'esportazione in formato XML si può effettuare:</p>
	<ul>
		<li><strong>singolarmente</strong> dalla pagina di un singolo ordine, cliccando sul pulsante "Esporta Fattura Elettronica"</li>
		<li><strong>per blocchi</strong> dalla <a href="/wp-admin/edit.php?post_type=shop_order">pagina degli ordini</a>, selezionando gli ordini da esportare e cliccando sul pulsante "Esporta Fatture Elettroniche". Le fatture esportate sono contenute in un unico file .zip</li>
	</ul>
	<p>A differenza di quelle destinate alla PA, le fatture elettroniche destinate a privati possono <strong>non essere firmate digitalmente</strong></p>
	<p>I file delle fatture possono essere trasmessi direttamente al Sistema di Interscambio via Posta Elettronica Certificata all'indirizzo <a href="mailto:sdi01@pec.fatturapa.it">sdi01@pec.fatturapa.it</a> oppure usando un software online di controllo, trasmissione e conservazione, come <a href="https://www.agenziaentrate.gov.it/wps/content/Nsilib/Nsi/Schede/Comunicazioni/Fatture+e+corrispettivi/Acc+servizio+Fatture+e+corrispettivi/?page=schedecomunicazioni" target="_blank">Fatture e corrispettivi</a> dell'Ag. Entrate.</p>
	<p><strong>È sempre importante verificare preliminarmente la correttezza dei file XML da trasmettere.</strong><br>
Fatelo tramite gli strumenti di controllo forniti dallo stesso Sistema di Interscambio:</p>
	<ul>
		<li><a href="https://ivaservizi.agenziaentrate.gov.it/ser/fatturewizard/#/controllo" target="_blank">Controlla fattura (accesso riservato CNS o SPID)</a></li>
		<li><a href="http://sdi.fatturapa.gov.it/SdI2FatturaPAWeb/AccediAlServizioAction.do?pagina=controlla_fattura" target="_blank">Controllare la Fattura PA</a> (funziona anche per le fatture a privati)</li>
	</ul>
</div>
</div>
<style>
.wcpdf_it_blocks{display: flex; flex-direction: row;flex-wrap: wrap;justify-content: space-between}
.wcpdf_it_block1{margin-bottom: 20px; width: 67%; padding-right: 24px; box-sizing: border-box}
.wcpdf_it_block2{margin-bottom: 20px; width: 33%; box-sizing: border-box}
.wcpdf_it_block2 li{list-style: square; margin-left: 1.2rem;}
.wcpdf_it_block2 .avviso{padding: 0.1rem 1rem; background-color: #EEF754}
.form-table input[type="text"], .form-table select, .form-table textarea, .woocommerce #wcpdf-IT-settings  table.form-table .select2-container {max-width: 100%; min-width: 0 !important; width: 400px}
</style>
<script type="text/javascript">
jQuery(function() {
	if(typeof(jQuery.fn.select2) === "function") {
		jQuery(".form-table select").each(function (){
			var placeholder = jQuery(this).data("placeholder");
			jQuery(this).select2({
				"placeholder": placeholder,
			});
		});
	}
});
</script>
<?php
		
		}

		function init_settings(){
			$page = $section = $option_name = WCPDF_IT()->settings->fpa_settings_key;
			$section_settings[] = array(
				'title' => __( 'Electronic Invoicing settings', WCPDF_IT_DOMAIN ),
				'id' => 'fpa_settings',
				//'description' => __( "", WCPDF_IT_DOMAIN ),
				'fields' => array(
					array(
						'title' => __( 'Adds the fields for Electronic Invoicing', WCPDF_IT_DOMAIN ),
						'callback' => 'radio_element_callback',
						'args' => array(
							'option_name' => $option_name,
							'id' => 'add_FPA',
							'description' => __( "It adds the necessary fields for Electronic Invoicing if the customer is an Italian company.", WCPDF_IT_DOMAIN ),
							'default' => "0",
							'options' => array(
								"0"	=> __( "No" , WCPDF_IT_DOMAIN ),
								"1"	=> __( "Add fields for Electronic Invoicing" , WCPDF_IT_DOMAIN ),
							)
						)
					),
				)
			);

			WCPDF_IT()->settings->add_settings_fields( $page, $section_settings, $section);
			return;
		}
		
	} // end class

} // end class_exists

return new WooCommerce_Italian_add_on_Settings_fpa();
