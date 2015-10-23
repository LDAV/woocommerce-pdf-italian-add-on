=== WooCommerce PDF Invoices Italian Add-on ===
Contributors: labdav
Tags: WooCommerce, Codice Fiscale, Partita IVA, VAT number, VAT, EU VAT, Fiscal Code, Invoice, Receipt, WooCommerce PDF Invoices & Packing Slips, ldav
Requires at least: 3.8
Tested up to: 4.3
Stable tag: 0.4.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl.html
Italian Add-on for PDF invoices & packing slips for WooCommerce.
Donate link: http://ldav.it/plugin/woocommerce-pdf-invoices-italian-add-on/

== Description ==

It adds all you need for your Italian WooCommerce e-shop to the Ewout Fernhout's [WooCommerce PDF Invoices & Packing Slips plugin](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/).

Fiscal code or VAT number are added to WooCommerce checkout page. The customer could choice between Invoice or Receipt option. In case of Invoice VAT is complimentary.

1. Read the istructions for [How to create my own custom template](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/faq/).
1. Copy the "[plugins dir]/woocommerce-pdf-italian-add-on/template/Simple/receipt.php" in your (child) theme, and customize it.

= IT =

Questo plugin aggiunge tutto quello che è necessario per un' e-shop italiano. Per funzionare ha bisogno del plugin gratuito di Ewout Fernhout's [WooCommerce PDF Invoices & Packing Slips plugin](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/) che consente di numerare automaticamente le fatture e di stamparle o inviarle in formato PDF.

Il Codice Fiscale o il Numero della Partita IVA vengono aggiunti alla pagina del checkout. Il cliente può scegliere fra Fattura o Ricevuta. Nel caso in cui scelga la Fattura, il campo Partita IVA è obbligatorio.

Il plugin [WooCommerce PDF Invoices & Packing Slips plugin](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/) permette di definire i template della fattura. Con questo add-on va definito anche il template della ricevuta:

1. Segui le istruzioni per [Come personalizzare il mio template](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/faq/).
1. Copia il file "[plugins dir]/woocommerce-pdf-italian-add-on/template/Simple/receipt.php" nel tuo tema (o tema figlio) e modificalo come credi opportuno.

Nel caso di una ricevuta, come ovvio, la numerazione automatica delle fatture viene sospesa e riprende solamente per le successive fatture.

Tested up to/Testato fino alle versioni:

* WooCommerce v. 2.4.4
* WooCommerce PDF Invoices & Packing Slips [Ewout Fernhout] v. 1.5.21
* WooCommerce PDF Invoice [RightPress] v. 2.1.6

= Features =

No special features, yet.

= Translations in your language =

* English
* Italian (it_IT)

== Installation ==

Steps to install this plugin.

Be sure you're running WooCommerce 2.0+ and WooCommerce PDF Invoices & Packing Slips

1. In the downloaded zip file, there is a folder with name 'autoSKU'
1. Upload the 'woocommerce-pdf-italian-add-on' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Copy "invoice.php" to "receipt.php" in PDF Template directory, and customize it.
1. Read the usage instructions in the description.


== Frequently Asked Questions ==

= Come posso personalizzare le ricevute, le fatture o i documenti di trasporto? =
1. Nella cartella wp-content/plugins/woocommerce-pdf-italian-add-on/templates/Simple/ trovi un modello di ricevuta (receipt.php). Copialo nel tuo tema nella cartella wp-content/themes/ilmiotema/woocommerce/pdf/ilmiotemplate.
1. Fai la stessa operazione con tutti i file della cartella wp-content/plugins/woocommerce-pdf-invoices-packing-slips/templates/pdf/Simple/ del plugin WC-PDF Invoice & Packing Slips. Copiali nel tuo tema, nella cartella wp-content/themes/ilmiotema/woocommerce/pdf/ilmiotemplate.
1. Una volta copiati i file vai nel pannello impostazioni (WooCommerce -> Fatture PDF -> Modello) e seleziona il modello ilmiotemplate dall'elenco a discesa.
1. Modifica i file receipt.php, invoice.php e packing-slip.php come meglio credi.
1. Ripeti queste operazioni per avere altri modelli di fattura o ricevuta a disposizione.

= Dove trovo altri modelli? =
Se non hai dimestichezza con la personalizzazione dei modelli, sul sito wpovernight.com trovi puoi scegliere fra tanti altri modelli. Se poi hai bisogno di un servizio ad-hoc contatta support@wpovernight.com.

= Come posso dare una mano? =
Ogni suggerimento o aiuto è bene accetto! Il plugin è su github: https://github.com/LDAV/woocommerce-pdf-italian-add-on

== Screenshots ==

1. Nella pagina del checkout aggiunge la possibilità di scegliere fra Fattura o Ricevuta e di inserire un Codice Fiscale o Partita IVA
2. Le stesse opzioni riportate nei dettagli dell'ordine, nell'indirizzo di fatturazione...
3. ... e nella pagina dell'utente.
4. Il codice Fiscale o la Partita IVA vengono riportate nei dati dell'indirizzo, e quindi anche nelle email o nel pdf di fattura o ricevuta

== Changelog ==

= 0.4.4 - 2015/08/12 =

IT

* Migliorata la Regular Expression per il riconoscimento di alcune Partite IVA europee.
* Aggiunta la colonna Fattura/Ricevuta nell'elenco ordini.
* Il plugin è compatibile anche con WooCommerce PDF Invoice [RightPress], oltre che con WooCommerce PDF Invoices & Packing Slips.
* Aggiornamenti vari del codice sorgente.

= 0.4.3 - 2015/06/12 =

IT

* L'opzione Fattura/Ricevuta viene formattata come gli altri elementi a discesa, se è registrato select2.js come di default su WooCommerce.

EN

* The Invoice/Receipt option is formatted as the other drop-down select fields, if select2.js is registered as is the default on WooCommerce.

= 0.4 - 2015/04/17 =

Thanks to [Ewout Fernhout](http://www.wpovernight.com/) for the valuable advice.

IT

* Aggiunto un modello di ricevuta da usare come riferimento. È nella cartella /template/Simple/. Copialo nel tuo tema per evitare che venga sovrascritto.
* Le ricevute vengono allegate alle email.
* È possibile scaricare la ricevuta dalla pagina My Account.
* Ora puoi stampare le ricevute direttamente dalla lista ordini.

EN

* A receipt template is added in /template/Simple/ folder. Copy it in your own theme to avoid to be rewritten.
* Receipts are now attached to emails.
* Receipt download option added to My Account page.
* You can print receipts from Orders list.

= 0.3 - 2015/03/17 =

IT

* Le impostazioni di Fattura o Ricevuta e codice fiscale / partita IVA, registrate con l'utente, vengono riportate nei checkout successivi;
* Aggiornamenti per compatibilità con Woocommerce 2.3.6  (grazie a ECom Store e fburatti)

EN

* User additional settings are called in checkout;
* Minor updates due to Woocommerce 2.3.6 (thanks to ECom Store and fburatti)

= 0.2b - 2015/02/17 =

Added an additional gettext call to allow users to override default plugin translations (thanks to Salaros https://github.com/salaros)

= 0.2 - 2015/02/01 =

IT

* Se è selezionata l'opzione Fattura controlla se la partita IVA è scritta correttamente, secondo le regole Europee;
* Se viene indicato un codice fiscale (opzione Ricevuta) effettua un controllo di conformità, valido anche in caso di omocodia.
* Corretti alcuni errori che intervenivano nel caso di stampa o invio di email multiplo (grazie a Francesco Giannetta)

EN

* If invoice option is selected then check if VAT Number is correct (according European rules);
* Fixed some errors in case of multiple actions (print or email sending) (thanks to Francesco Giannetta);

= 0.1 - 2015/01/02 =
* Initial version of plugin

== Upgrade Notice ==

= 0.3.3 =
Abbiamo aggiunto un paio di funzioni che migliorano la compatibilità del plugin. È consigliato l'upgrade. 

== Support ==

Se ritieni ci sia un problema [puoi segnalarcelo anche su GitHub](https://github.com/LDAV/woocommerce-pdf-italian-add-on)

If you find an issue, [let us know here!](https://github.com/LDAV/woocommerce-pdf-italian-add-on)

== Contributions ==

Anyone is welcome to contribute to the plugin. There are various ways you can contribute:

[Raise an issue](https://github.com/LDAV/woocommerce-pdf-italian-add-on) on GitHub.

Send us a Pull Request with your bug fixes and/or new features.

Provide feedback and [suggestions on enhancements](https://github.com/LDAV/woocommerce-pdf-italian-add-on/issues).

Translate the plugin into your language. You can download the latest .po/.mo file [here](https://github.com/LDAV/woocommerce-pdf-italian-add-on/tree/master/trunk/languages).